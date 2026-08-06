<?php
/**
 * Open-tracking pixel.
 *
 * Returns a 1x1 transparent GIF and notes that the message was opened. Many
 * mail clients block remote images, so a missing open never means the email
 * was not read. It is a floor, not a measurement.
 */
define('PHELYZ_ACCESS', true);
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$token = isset($_GET['m']) ? preg_replace('/[^A-Za-z0-9]/', '', $_GET['m']) : '';

if ($token !== '') {
    try {
        getDB()->query(
            "UPDATE email_log
                SET open_count     = open_count + 1,
                    opened_at      = COALESCE(opened_at, NOW()),
                    last_opened_at = NOW()
              WHERE token = ?",
            [$token]
        );
    } catch (Exception $e) {
        // A tracking failure must never show the reader a broken image.
    }
}

// Never let a proxy cache this, or the second open is invisible.
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Length: 43');

echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
