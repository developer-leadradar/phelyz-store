<?php
/**
 * Phelyz Store — Vercel PHP Entry Point
 * All requests route through here; this is the only PHP lambda.
 */

// Project root (one level up from api/)
$root = dirname(__DIR__);

// Parse requested path (strip query string)
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = '/' . ltrim($uri, '/');

// ── Security: block access to sensitive files ──────────────────────────────
$blocked = ['.env', 'config.php', '/data/', '/vendor/', '/.git/'];
foreach ($blocked as $b) {
    if (stripos($uri, $b) !== false) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// ── Resolve target PHP file ────────────────────────────────────────────────
function resolvePhpFile(string $root, string $uri): ?string {
    // Direct PHP file
    $direct = $root . $uri;
    if (is_file($direct) && pathinfo($direct, PATHINFO_EXTENSION) === 'php') {
        return $direct;
    }

    // Directory → index.php
    $dir = rtrim($root . $uri, '/');
    if (is_dir($dir) && is_file($dir . '/index.php')) {
        return $dir . '/index.php';
    }

    // Root
    if ($uri === '/' && is_file($root . '/index.php')) {
        return $root . '/index.php';
    }

    return null;
}

$targetFile = resolvePhpFile($root, $uri);

if ($targetFile === null) {
    http_response_code(404);
    if (is_file($root . '/404.php')) {
        chdir(dirname($root . '/404.php'));
        require $root . '/404.php';
    } else {
        echo '<!DOCTYPE html><html><body><h1>404 — Page Not Found</h1></body></html>';
    }
    exit;
}

// ── Set script context so relative paths inside each file work ─────────────
$_SERVER['SCRIPT_FILENAME'] = $targetFile;
$_SERVER['SCRIPT_NAME']     = '/' . ltrim(str_replace($root, '', $targetFile), '/');
$_SERVER['PHP_SELF']        = $_SERVER['SCRIPT_NAME'];

// Change working directory to the target file's folder so that
// require/include with relative paths (e.g. "includes/header.php") resolve correctly
chdir(dirname($targetFile));

// ── Execute ────────────────────────────────────────────────────────────────
require $targetFile;
