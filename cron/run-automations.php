<?php
/**
 * Cron entry point for the email automations.
 *
 * Set this up in cPanel under "Cron Jobs", every 30 minutes:
 *
 *   /usr/local/bin/php -q /home/cimedgec/repositories/phelyz-store/cron/run-automations.php
 *
 * If cPanel will only run it over the web, use the URL form instead and set
 * CRON_TOKEN in .env to something long and random:
 *
 *   curl -s "https://phelyzstore.com/cron/run-automations.php?token=YOUR_TOKEN"
 *
 * Running it twice by accident is harmless: every send is written to
 * email_automation_log first, and the unique key there stops repeats.
 */

define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/automations.php';

$isCli = (php_sapi_name() === 'cli');

// Over the web this must be protected, or anyone could hammer the mail queue.
if (!$isCli) {
    header('Content-Type: text/plain');
    $expected = getenv('CRON_TOKEN') ?: '';
    $given    = $_GET['token'] ?? '';
    if ($expected === '' || !hash_equals($expected, (string)$given)) {
        http_response_code(403);
        echo "Forbidden. Set CRON_TOKEN in .env and pass ?token=\n";
        exit;
    }
}

// A slow mail server must not leave the job half done.
set_time_limit(300);
ignore_user_abort(true);

automationEnsureRows();

// Leave a breadcrumb so the admin panel can show that the schedule is alive,
// rather than telling the shop to set up a cron job that is already running.
$stampDir = __DIR__ . '/../data';
if (!is_dir($stampDir)) @mkdir($stampDir, 0755, true);
@file_put_contents($stampDir . '/cron-last-run.txt', date('c'));

// Sweep up card orders that were never paid for. These are created just before
// the customer is sent to Paystack; if they close the tab instead of paying,
// neither the callback nor the webhook ever fires and the order would otherwise
// sit in "pending" for ever, looking like a real sale waiting to be packed.
// Two hours is long enough for a slow bank transfer inside Paystack to land.
$abandoned = 0;
try {
    $stmt = getDB()->query(
        "UPDATE orders
            SET status = 'cancelled', payment_status = 'failed'
          WHERE payment_method = 'paystack'
            AND payment_status = 'pending'
            AND status = 'pending'
            AND created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)"
    );
    if ($stmt) $abandoned = $stmt->rowCount();
} catch (Exception $e) {
    // Never let a housekeeping failure stop the email run.
}

$result = automationRunAll();

$stamp = date('Y-m-d H:i:s');
if (!empty($result['skipped_quiet_hours'])) {
    echo "[$stamp] Quiet hours, nothing sent.\n";
    echo "  Abandoned card orders cancelled: " . $abandoned . "\n";
    exit;
}

$total = array_sum($result['sent']);
echo "[$stamp] Automated emails sent: $total\n";
foreach ($result['sent'] as $key => $n) {
    echo "  - $key: $n\n";
}
echo "  Scheduled campaigns started: " . (int)$result['scheduled_campaigns'] . "\n";
echo "  Abandoned card orders cancelled: " . $abandoned . "\n";
