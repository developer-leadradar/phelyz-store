<?php
// Phelyz Store - Configuration File
// Prevent direct access
if (!defined('PHELYZ_ACCESS')) {
    define('PHELYZ_ACCESS', true);
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'phelyz_store_new');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Configuration
define('SITE_NAME', 'Phelyz Store');
define('SITE_URL', 'http://localhost/phelyz-store'); // Change to live domain in production
define('SITE_EMAIL', 'info@phelyz.com');

// ── SMTP Email Configuration ─────────────────────────────────────────────────
// Gmail: use an App Password — myaccount.google.com > Security > 2-Step > App passwords
define('SMTP_HOST',       'smtp.gmail.com');    // smtp.gmail.com | smtp.mailgun.org | smtp.sendgrid.net
define('SMTP_PORT',       587);                 // 587 = TLS (recommended) | 465 = SSL
define('SMTP_ENCRYPTION', 'tls');               // 'tls' or 'ssl'
define('SMTP_USERNAME',   'your@gmail.com');    // Your SMTP login
define('SMTP_PASSWORD',   'your_app_password'); // App Password, NOT your real password
define('SMTP_FROM_EMAIL', 'info@phelyz.com');   // Sender address shown to recipient
define('SMTP_FROM_NAME',  'Phelyz Store');      // Sender name shown to recipient
define('SITE_PHONE', '+234 800 000 0000');
define('SITE_WHATSAPP', '+2348000000000'); // WhatsApp number

// Path Configuration
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Session Configuration
session_start();

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Africa/Lagos');

// Security Settings
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 12);
?>