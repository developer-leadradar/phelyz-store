<?php
if (!defined('PHELYZ_ACCESS')) { define('PHELYZ_ACCESS', true); }

// ── Load .env for local development ─────────────────────────────────────────
function _loadEnv($path) {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($k, $_ENV) && !getenv($k)) {
            $_ENV[$k] = $v;
            putenv("$k=$v");
        }
    }
}
_loadEnv(__DIR__ . '/.env');

// ── Database ─────────────────────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'phelyz_store_new');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql'); // 'mysql' or 'pgsql'

// ── Site ─────────────────────────────────────────────────────────────────────
define('SITE_NAME',  getenv('SITE_NAME')  ?: 'Phelyz Store');
define('SITE_URL',   rtrim(getenv('SITE_URL')   ?: 'http://localhost/phelyz-store', '/'));
define('SITE_EMAIL', getenv('SITE_EMAIL') ?: 'support@phelyzstore.com');
define('SITE_PHONE', getenv('SITE_PHONE') ?: '+234 902 403 3207');
define('SITE_WHATSAPP', getenv('SITE_WHATSAPP') ?: '+2349024033207');
define('SITE_ADDRESS', getenv('SITE_ADDRESS') ?: 'Uyo, Akwa Ibom State, Nigeria');
define('SITE_HOURS',   getenv('SITE_HOURS')   ?: 'Mon - Sat: 9:00 AM - 6:00 PM');

// ── Social accounts ──────────────────────────────────────────────────────────
// Leave a value blank and its icon simply disappears from the site, so we never
// show a link to an account that does not exist.
define('SOCIAL_INSTAGRAM', getenv('SOCIAL_INSTAGRAM') ?: 'https://www.instagram.com/_phelyz_stores');
define('SOCIAL_TIKTOK',    getenv('SOCIAL_TIKTOK')    ?: 'https://www.tiktok.com/@phelyz_stores');
define('SOCIAL_FACEBOOK',  getenv('SOCIAL_FACEBOOK')  ?: 'https://www.facebook.com/share/1Eq9mDH5yC/');
define('SOCIAL_TWITTER',   getenv('SOCIAL_TWITTER')   ?: '');
define('SOCIAL_PINTEREST', getenv('SOCIAL_PINTEREST') ?: '');

// ── Email (Resend) ────────────────────────────────────────────────────────────
define('RESEND_API_KEY',   getenv('RESEND_API_KEY')   ?: '');
define('SMTP_FROM_EMAIL',  getenv('SMTP_FROM_EMAIL')  ?: 'support@phelyzstore.com');
define('SMTP_FROM_NAME',   getenv('SMTP_FROM_NAME')   ?: 'Phelyz Store');

// Legacy SMTP constants kept for compatibility
define('SMTP_HOST',       getenv('SMTP_HOST')       ?: 'smtp.gmail.com');
define('SMTP_PORT',       (int)(getenv('SMTP_PORT')       ?: 587));
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');
define('SMTP_USERNAME',   getenv('SMTP_USERNAME')   ?: '');
define('SMTP_PASSWORD',   getenv('SMTP_PASSWORD')   ?: '');

// ── Supabase Storage ──────────────────────────────────────────────────────────
define('SUPABASE_URL',         getenv('SUPABASE_URL')         ?: '');
define('SUPABASE_SERVICE_KEY', getenv('SUPABASE_SERVICE_KEY') ?: '');
define('SUPABASE_BUCKET',      getenv('SUPABASE_BUCKET')      ?: 'product-images');

// ── Uploads (local fallback) ──────────────────────────────────────────────────
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL',  SITE_URL . '/uploads/');

// ── Security ──────────────────────────────────────────────────────────────────
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 12);

// Used to sign one-click links (e.g. email unsubscribe) so they cannot be
// guessed. Set APP_SECRET in .env on the server to something long and random.
define('APP_SECRET', getenv('APP_SECRET') ?: ('phelyz-fallback-' . DB_NAME . DB_USER));

// ── Error reporting ───────────────────────────────────────────────────────────
// This used to decide "am I in production?" by looking for Vercel's own
// environment variables. Those stopped existing the day the site moved to
// cPanel, so the answer became "no" on the live server and every visitor to
// phelyzstore.com was shown raw PHP warnings, complete with the full path to
// the code on disk.
//
// It now defaults to production and only relaxes for a recognisably local
// machine, so a new environment can never be noisy by accident. Set APP_ENV in
// .env to override.
$appEnv = strtolower(trim((string)(getenv('APP_ENV') ?: '')));
if ($appEnv === '') {
    $host    = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || substr($host, -6) === '.local'
            || substr($host, -5) === '.test';
    $appEnv  = $isLocal ? 'development' : 'production';
}
define('APP_ENV', $appEnv);
$isProduction = (APP_ENV === 'production');

// Report everything worth knowing about, but never to the visitor's screen.
// Deprecation notices are left out of the log so they cannot drown the real
// errors. On the server these land in cPanel's error log.
error_reporting($isProduction ? (E_ALL & ~E_DEPRECATED & ~E_STRICT) : E_ALL);
ini_set('display_errors', $isProduction ? '0' : '1');
ini_set('log_errors', '1');

// ── Timezone ──────────────────────────────────────────────────────────────────
date_default_timezone_set('Africa/Lagos');

// ── Session handler (PostgreSQL for production, file-based for local) ─────────
if (DB_DRIVER === 'pgsql') {
    require_once __DIR__ . '/includes/session_handler.php';
    $handler = new PgSessionHandler();
    session_set_save_handler($handler, true);
}

// ── Output buffering ──────────────────────────────────────────────────────────
// Vercel's PHP runtime has output_buffering off; without a buffer, any HTML
// emitted before redirect() kills the Location header. Buffer everything.
if (ob_get_level() === 0) { ob_start(); }

// ── Start session ─────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    // Keep PHP's garbage collector in step with the session store's own 24h TTL.
    // PHP defaults gc_maxlifetime to 1440s (24 min) and calls gc() with THAT
    // value, so sessions were being deleted after 24 minutes even though the
    // handler considered them valid for a day.
    ini_set('session.gc_maxlifetime', 86400);

    // PHP 7+ enables lazy_write, which skips write() when the session data
    // hasn't changed. Simply browsing never changes it, so last_activity was
    // never refreshed and an active user still aged out. Write every request
    // so activity actually keeps the session alive.
    ini_set('session.lazy_write', '0');

    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'secure'   => $isProduction,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
