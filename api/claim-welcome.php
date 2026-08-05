<?php
define('PHELYZ_ACCESS', true);
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true) ?: [];
$email    = strtolower(trim((string)($data['email'] ?? '')));
$whatsapp = trim((string)($data['whatsapp'] ?? ''));
$code     = 'WELCOME10';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'That email does not look right.']);
    exit;
}
if (strlen(preg_replace('/\D/', '', $whatsapp)) < 10) {
    echo json_encode(['success' => false, 'message' => 'Please enter a full WhatsApp number.']);
    exit;
}

$db = getDB();

// Record the lead. A repeat claim just refreshes the number rather than
// failing, since the visitor only cares about getting their code.
try {
    $existing = $db->fetchOne("SELECT id FROM leads WHERE email = ?", [$email]);
    if ($existing) {
        $db->update('leads', ['whatsapp' => $whatsapp, 'coupon_code' => $code], 'id = ?', [$existing['id']]);
    } else {
        $db->insert('leads', [
            'email'       => $email,
            'whatsapp'    => $whatsapp,
            'source'      => 'welcome_popup',
            'coupon_code' => $code,
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Could not save that. Run migrations/add_automation_and_expenses.sql.']);
    exit;
}

// Send the code over. A failure here must not lose the lead we just captured,
// so the visitor is still shown the code on screen either way.
try {
    $html = phelyzEmailTemplate(
        '<p style="margin:0 0 12px;font-size:16px;">Welcome to Phelyz Store.</p>'
      . '<p style="margin:0 0 18px;color:#44403C;">Here is the 10% off your first piece, as promised. '
      . 'Enter it at checkout.</p>'
      . '<div style="text-align:center;margin:22px 0;">'
      . '<span style="display:inline-block;font-family:Georgia,serif;font-size:26px;font-weight:bold;'
      . 'letter-spacing:3px;color:#1C1917;background:#FAFAF9;border:2px dashed #CA8A04;border-radius:10px;padding:14px 28px;">'
      . htmlspecialchars($code) . '</span></div>'
      . phelyzEmailButton('Start shopping', SITE_URL . '/shop.php')
      . '<p style="margin:0;color:#78716C;font-size:13px;">The code works on your first order and applies to '
      . 'everything except made-to-order Express pieces.</p>',
        'Your 10% welcome code for Phelyz Store.'
    );
    sendEmail($email, 'Your 10% welcome code', $html);
} catch (Exception $e) {
    error_log('Welcome code email failed: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'code' => $code]);
