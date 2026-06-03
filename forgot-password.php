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
            $message   = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px;"><tr><td align="center"><table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;"><tr><td style="background:#1C1917;padding:36px 40px;text-align:center;"><h1 style="color:#CA8A04;font-size:26px;font-weight:800;letter-spacing:3px;margin:0;">PHELYZ</h1><p style="color:rgba(255,255,255,0.6);font-size:13px;margin:6px 0 0;">Password Reset Request</p></td></tr><tr><td style="padding:40px;"><p style="color:#1C1917;font-size:16px;margin:0 0 12px;">Hello <strong>'.htmlspecialchars($user['first_name']).'</strong>,</p><p style="color:#44403C;font-size:15px;line-height:1.7;margin:0 0 28px;">We received a request to reset your password. This link expires in 1 hour.</p><div style="text-align:center;margin:32px 0;"><a href="'.$resetLink.'" style="display:inline-block;background:#CA8A04;color:#ffffff;text-decoration:none;padding:16px 40px;border-radius:8px;font-size:16px;font-weight:700;">Reset My Password</a></div><p style="color:#78716C;font-size:13px;margin:0 0 8px;">Or copy this link:</p><p style="background:#f8f9fb;border:1px solid #e4e8ef;border-radius:6px;padding:12px;font-size:12px;word-break:break-all;">'.$resetLink.'</p></td></tr></table></td></tr></table></body></html>';
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
      <div style="font-family:'Cormorant',serif;font-size:36px;font-weight:700;color:white;letter-spacing:0.06em;">PHELYZ</div>
      <p style="font-size:15px;color:rgba(255,255,255,0.65);margin-top:10px;">Premium diamonds and fine jewelry.</p>
    </div>
  </div>
  <div class="auth-panel-right">
    <div class="auth-form-inner">
      <div class="auth-logo-text">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M6.5 2h11l4 6-9.5 14L2.5 8l4-6z"/></svg>
        PHELYZ
      </div>
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
