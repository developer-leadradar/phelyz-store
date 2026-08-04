<?php
$pageTitle = "Unsubscribe";
require_once 'includes/header.php';
require_once __DIR__ . '/includes/email-campaigns.php';

$db     = getDB();
$email  = isset($_GET['e']) ? trim($_GET['e']) : '';
$token  = isset($_GET['t']) ? trim($_GET['t']) : '';
$state  = 'invalid'; // invalid | done | already | resubscribed

// The link is signed, so only addresses we actually mailed can be opted out.
$valid = $email !== '' && $token !== '' && hash_equals(campaignUnsubToken($email), $token);

if ($valid) {
    $existing = null;
    try {
        $existing = $db->fetchOne("SELECT id FROM email_unsubscribes WHERE email = ?", [strtolower($email)]);
    } catch (Exception $e) {}

    if (isset($_GET['undo'])) {
        try {
            $db->delete('email_unsubscribes', 'email = ?', [strtolower($email)]);
            $state = 'resubscribed';
        } catch (Exception $e) { $state = 'invalid'; }
    } elseif ($existing) {
        $state = 'already';
    } else {
        try {
            $db->insert('email_unsubscribes', ['email' => strtolower($email)]);
            $state = 'done';
        } catch (Exception $e) {
            $state = 'already';
        }
    }
}
?>

<div style="min-height:calc(100vh - 220px);display:flex;align-items:center;justify-content:center;padding:60px 20px;background:var(--cream);">
  <div style="background:#fff;border:1px solid var(--cream-dark);border-radius:20px;max-width:520px;width:100%;text-align:center;overflow:hidden;box-shadow:0 8px 40px rgba(28,25,23,0.07);">
    <div style="height:4px;background:linear-gradient(90deg,var(--gold),#D97706,var(--gold));"></div>
    <div style="padding:44px 36px;">

      <img src="<?php echo SITE_URL; ?>/assets/images/phelyz-logo.svg" alt="<?php echo htmlspecialchars(SITE_NAME); ?>"
           style="height:46px;width:auto;margin-bottom:26px;">

      <?php if ($state === 'done' || $state === 'already'): ?>
        <h1 style="font-family:'Cormorant',serif;font-size:27px;font-weight:700;color:var(--black);margin:0 0 12px;">
          You are unsubscribed
        </h1>
        <p style="font-size:14.5px;color:var(--stone-mid);line-height:1.7;margin:0 0 8px;">
          We will not send <strong style="color:var(--black);"><?php echo htmlspecialchars($email); ?></strong> any more offers or news.
        </p>
        <p style="font-size:13px;color:var(--stone-mid);line-height:1.7;margin:0 0 26px;">
          You will still get emails about orders you place, such as receipts and delivery updates.
        </p>
        <a href="?e=<?php echo urlencode($email); ?>&t=<?php echo urlencode($token); ?>&undo=1"
           style="font-size:13px;color:var(--gold);font-weight:600;">Changed your mind? Resubscribe</a>

      <?php elseif ($state === 'resubscribed'): ?>
        <h1 style="font-family:'Cormorant',serif;font-size:27px;font-weight:700;color:var(--black);margin:0 0 12px;">
          Welcome back
        </h1>
        <p style="font-size:14.5px;color:var(--stone-mid);line-height:1.7;margin:0 0 26px;">
          <strong style="color:var(--black);"><?php echo htmlspecialchars($email); ?></strong> will receive our news and offers again.
        </p>

      <?php else: ?>
        <h1 style="font-family:'Cormorant',serif;font-size:27px;font-weight:700;color:var(--black);margin:0 0 12px;">
          Link not valid
        </h1>
        <p style="font-size:14.5px;color:var(--stone-mid);line-height:1.7;margin:0 0 26px;">
          This unsubscribe link is incomplete or has been changed. Please use the link exactly as it appears in the email, or contact us and we will remove you.
        </p>
        <a href="contact.php" class="btn btn-gold">Contact us</a>
      <?php endif; ?>

      <div style="margin-top:30px;padding-top:20px;border-top:1px solid var(--cream-dark);">
        <a href="<?php echo SITE_URL; ?>" style="font-size:13px;color:var(--stone-mid);">Back to Phelyz Store</a>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
