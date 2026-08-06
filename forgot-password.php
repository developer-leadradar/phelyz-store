<?php
$pageTitle = "Forgot Password";
require_once 'includes/header.php';

if (isLoggedIn()) redirect('customer-dashboard.php');

$db      = getDB();
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $user = $db->fetchOne("SELECT id, first_name FROM users WHERE email = ? AND is_active = 1 AND role = 'customer'", [$email]);

        if ($user) {
            $db->delete('password_resets', 'email = ?', [$email]);
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $db->insert('password_resets', ['email'=>$email,'token'=>$token,'expires_at'=>$expiresAt]);
            $resetLink = SITE_URL . '/reset-password.php?token=' . $token;
            $subject   = 'Reset Your Phelyz Store Password';
            $message   = phelyzEmailTemplate(
                '<p style="margin:0 0 12px;font-size:16px;">Hello,</p>'
              . '<p style="margin:0 0 4px;color:#44403C;">We received a request to reset the password on your Phelyz Store account. Click the button below to choose a new one.</p>'
              . phelyzEmailButton('Reset My Password', $resetLink)
              . '<p style="margin:0 0 8px;color:#78716C;font-size:13px;">Or paste this link into your browser:</p>'
              . '<p style="background:#FAFAF9;border:1px solid #E7E5E4;border-radius:6px;padding:12px;font-size:12px;color:#44403C;word-break:break-all;margin:0 0 22px;">' . htmlspecialchars($resetLink) . '</p>'
              . '<p style="margin:0;color:#78716C;font-size:13px;">This link expires in 1 hour. If you did not ask for a password reset, you can safely ignore this email and nothing will change.</p>',
                'Reset the password on your Phelyz Store account.'
            );
            emailContext(['category'=>'transactional','source_type'=>'password_reset']);
            sendEmail($email, $subject, $message);
        }
        $success = 'If that email is registered, you will receive a password reset link shortly.';
    }
}
?>

<div class="auth-split">
  <div class="auth-panel-left">
    <img src="https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=700&h=900&fit=crop&q=80" alt="Jewelry">
    <div class="auth-panel-left-inner">
      <img src="<?php echo SITE_URL; ?>/assets/images/phelyz-logo-light.svg" alt="<?php echo htmlspecialchars(SITE_NAME); ?>" style="height:58px;width:auto;display:block;margin-bottom:14px;">
      <p style="font-size:15px;color:rgba(255,255,255,0.65);margin-top:10px;">Premium diamonds and fine jewelry.</p>
    </div>
  </div>
  <div class="auth-panel-right">
    <div class="auth-form-inner">
      <a href="<?php echo SITE_URL; ?>" style="display:block;margin-bottom:22px;"><img src="<?php echo SITE_URL; ?>/assets/images/phelyz-logo.svg" alt="<?php echo htmlspecialchars(SITE_NAME); ?>" class="auth-logo-img"></a>
      <h1 class="auth-heading">Reset password</h1>
      <p class="auth-sub">Enter your email and we'll send you a reset link</p>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <?php echo htmlspecialchars($success); ?>
        </div>
        <a href="login.php" class="btn btn-dark btn-full" style="margin-top:16px;">Back to Sign In</a>
      <?php else: ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
          <div class="form-group"><label class="form-label">Email Address</label><input type="email" name="email" class="form-input" required placeholder="your@email.com" autocomplete="email" value="<?php echo htmlspecialchars($_POST['email']??''); ?>"></div>
          <button type="submit" class="btn btn-gold btn-full" style="margin-bottom:16px;">Send Reset Link</button>
        </form>
        <p style="text-align:center;font-size:14px;color:var(--stone-mid);">Remember it? <a href="login.php" style="color:var(--gold);font-weight:700;">Sign in →</a></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
