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
$result = automationRunAll();

$stamp = date('Y-m-d H:i:s');
if (!empty($result['skipped_quiet_hours'])) {
    echo "[$stamp] Quiet hours, nothing sent.\n";
    exit;
}

$total = array_sum($result['sent']);
echo "[$stamp] Automated emails sent: $total\n";
foreach ($result['sent'] as $key => $n) {
    echo "  - $key: $n\n";
}
echo "  Scheduled campaigns started: " . (int)$result['scheduled_campaigns'] . "\n";
