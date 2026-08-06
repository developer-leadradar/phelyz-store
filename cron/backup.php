<?php
/**
 * Automated backup.
 *
 * The code is safe in git. This covers the part that is not: the database and
 * the product photos. Losing either would be unrecoverable, and the usual
 * causes are not dramatic. A bad migration, a mistaken delete, a host problem.
 *
 * Set this up in cPanel under Cron Jobs, once a day at about 3am:
 *
 *   /usr/local/bin/php -q /home/cimedgec/repositories/phelyz-store/cron/backup.php
 *
 * Backups are written OUTSIDE the site folder, so a deploy cannot wipe them
 * and nobody can download them over the web.
 *
 * Retention: 14 daily copies, plus the first backup of each month kept for a
 * year. That covers both "I broke it this morning" and "this went wrong weeks
 * ago and nobody noticed".
 */

define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain');
    $expected = getenv('CRON_TOKEN') ?: '';
    if ($expected === '' || !hash_equals($expected, (string)($_GET['token'] ?? ''))) {
        http_response_code(403);
        echo "Forbidden. Set CRON_TOKEN in .env and pass ?token=\n";
        exit;
    }
}

set_time_limit(900);
ignore_user_abort(true);

// Above the site folder, never inside it.
$root = getenv('BACKUP_PATH') ?: dirname(dirname(__DIR__)) . '/phelyz-backups';
$dbDir  = $root . '/database';
$imgDir = $root . '/uploads';
foreach ([$root, $dbDir, $imgDir] as $d) {
    if (!is_dir($d) && !@mkdir($d, 0700, true)) {
        fwrite(STDERR, "Cannot create $d\n");
        exit(1);
    }
}
// Belt and braces in case the folder ever ends up served.
@file_put_contents($root . '/.htaccess', "Require all denied\n");

$stamp = date('Y-m-d_His');
$log   = [];
function say($m) { global $log; $log[] = $m; echo $m . "\n"; }

// ── 1. Database ─────────────────────────────────────────────────────────────
$dbFile = $dbDir . "/phelyz_{$stamp}.sql";
$fh = fopen($dbFile, 'w');
if (!$fh) { say('FAILED: cannot open ' . $dbFile); exit(1); }

$pdo = getDB()->getConnection();
fwrite($fh, "-- Phelyz Store backup " . date('c') . "\n");
fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n");

$tables = [];
foreach ($pdo->query("SHOW TABLES") as $row) { $tables[] = array_values($row)[0]; }

$rowTotal = 0;
foreach ($tables as $t) {
    $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC);
    fwrite($fh, "\n-- ── $t ──\nDROP TABLE IF EXISTS `$t`;\n" . ($create['Create Table'] ?? '') . ";\n");

    // Streamed row by row: loading a whole table into memory is what makes
    // naive backup scripts fall over once the shop has real data in it.
    $stmt = $pdo->query("SELECT * FROM `$t`");
    $count = 0;
    $buffer = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $vals = array_map(function ($v) use ($pdo) {
            return $v === null ? 'NULL' : $pdo->quote((string)$v);
        }, array_values($r));
        $buffer[] = '(' . implode(',', $vals) . ')';
        $count++;
        if (count($buffer) >= 200) {
            $cols = '`' . implode('`,`', array_keys($r)) . '`';
            fwrite($fh, "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $buffer) . ";\n");
            $buffer = [];
        }
    }
    if ($buffer) {
        $keys = array_keys($r ?: []);
        $cols = '`' . implode('`,`', $keys) . '`';
        fwrite($fh, "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $buffer) . ";\n");
    }
    $rowTotal += $count;
}
fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
fclose($fh);

// Compress: these files are almost entirely repeated text and shrink hugely.
if (function_exists('gzopen')) {
    $gz = gzopen($dbFile . '.gz', 'wb9');
    $in = fopen($dbFile, 'rb');
    while (!feof($in)) { gzwrite($gz, fread($in, 262144)); }
    fclose($in); gzclose($gz);
    unlink($dbFile);
    $dbFile .= '.gz';
}
say('Database: ' . basename($dbFile) . ' (' . count($tables) . ' tables, '
    . number_format($rowTotal) . ' rows, ' . round(filesize($dbFile) / 1024) . ' KB)');

// ── 2. Product photos, weekly ───────────────────────────────────────────────
// A full image copy every night is wasteful; they change rarely and the folder
// only grows. Sunday, or whenever the newest copy is over a week old.
$imgLatest = glob($imgDir . '/uploads_*.zip');
$imgAge    = $imgLatest ? (time() - filemtime(end($imgLatest))) : PHP_INT_MAX;

if (class_exists('ZipArchive') && ($imgAge > 6 * 86400)) {
    $zipPath = $imgDir . "/uploads_{$stamp}.zip";
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $base = realpath(__DIR__ . '/../uploads');
        $n = 0;
        if ($base) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (!$file->isFile()) continue;
                $zip->addFile($file->getRealPath(), 'uploads/' . substr($file->getRealPath(), strlen($base) + 1));
                $n++;
            }
        }
        $zip->close();
        say('Photos: ' . basename($zipPath) . " ($n files, " . round(filesize($zipPath) / 1048576, 1) . ' MB)');
    } else {
        say('Photos: could not create the zip');
    }
} else {
    say('Photos: skipped, the current copy is recent enough');
}

// ── 3. Retention ────────────────────────────────────────────────────────────
// Keep 14 days of dailies, and the first backup of any month for a year.
function prune($dir, $pattern, $keepDays, $label) {
    $files = glob("$dir/$pattern");
    if (!$files) return 0;
    sort($files);
    $monthlyKept = [];
    $removed = 0;
    foreach ($files as $f) {
        $age = (time() - filemtime($f)) / 86400;
        if ($age <= $keepDays) continue;
        $month = date('Y-m', filemtime($f));
        // First surviving file of each month becomes that month's keeper.
        if (!isset($monthlyKept[$month]) && $age < 365) { $monthlyKept[$month] = true; continue; }
        if (@unlink($f)) $removed++;
    }
    if ($removed) say("Removed $removed old $label");
    return $removed;
}
prune($dbDir,  'phelyz_*.sql*',  14,  'database backups');
prune($imgDir, 'uploads_*.zip',  60,  'photo backups');

// ── 4. Manifest, so the admin panel can report on it ────────────────────────
$dbFiles  = glob($dbDir . '/phelyz_*.sql*') ?: [];
$imgFiles = glob($imgDir . '/uploads_*.zip') ?: [];
$totalBytes = 0;
foreach (array_merge($dbFiles, $imgFiles) as $f) { $totalBytes += filesize($f); }

$manifest = [
    'last_run'       => date('c'),
    'last_db_file'   => basename($dbFile),
    'last_db_bytes'  => filesize($dbFile),
    'db_copies'      => count($dbFiles),
    'photo_copies'   => count($imgFiles),
    'total_bytes'    => $totalBytes,
    'tables'         => count($tables),
    'rows'           => $rowTotal,
    'path'           => $root,
    'log'            => $log,
];
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
@file_put_contents($dataDir . '/backup-status.json', json_encode($manifest, JSON_PRETTY_PRINT));

say('Kept: ' . count($dbFiles) . ' database, ' . count($imgFiles) . ' photo copies, '
    . round($totalBytes / 1048576, 1) . ' MB total');
say('Stored in ' . $root);
