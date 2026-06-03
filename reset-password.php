<?php
$pageTitle = "Reset Password";
require_once 'includes/header.php';

if (isLoggedIn()) redirect('customer-dashboard.php');

$db          = getDB();
$token       = isset($_GET['token']) ? trim($_GET['token']) : '';
$error       = '';
$success     = '';
$resetRecord = null;

if ($token) {
    $resetRecord = $db->fetchOne("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()", [$token]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword     = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $postToken       = $_POST['token'] ?? '';
    $rec             = $db->fetchOne("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()", [$postToken]);
    if (!$rec) { $error = 'This reset link is invalid or has expired.'; }
    elseif (strlen($newPassword) < 6) { $error = 'Password must be at least 6 characters.'; }
    elseif ($newPassword !== $confirmPassword) { $error = 'Passwords do not match.'; }
    else {
        $hashed = password_hash($newPassword, PASSWORD_HASH_ALGO, ['cost'=>PASSWORD_HASH_COST]);
        $db->update('users', ['password'=>$hashed], 'email = ?', [$rec['email']]);
        $db->delete('password_resets', 'email = ?', [$rec['email']]);
        $success = 'Password reset successfully! You can now sign in with your new password.';
    }
}
?>

<div class="auth-split">
  <div class="auth-panel-left">
    <img src="https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=700&h=900&fit=crop&q=80" alt="Jewelry">
    <div class="auth-panel-left-inner">
      <div style="font-family:'Cormorant',serif;font-size:36px;font-weight:700;color:white;letter-spacing:0.06em;">PHELYZ</div>
    </div>
  </div>
  <div class="auth-panel-right">
    <div class="auth-form-inner">
      <div class="auth-logo-text"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M6.5 2h11l4 6-9.5 14L2.5 8l4-6z"/></svg>PHELYZ</div>
      <h1 class="auth-heading">New password</h1>
      <p class="auth-sub">Choose a strong new password for your account</p>

      <?php if ($success): ?>
        <div class="alert alert-success"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><?php echo htmlspecialchars($success); ?></div>
        <a href="login.php" class="btn btn-gold btn-full" style="margin-top:16px;">Sign In Now</a>
      <?php elseif (!$resetRecord && !$_POST): ?>
        <div class="alert alert-error">This reset link is invalid or has expired. Please request a new one.</div>
        <a href="forgot-password.php" class="btn btn-gold btn-full" style="margin-top:16px;">Request New Link</a>
      <?php else: ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?: ($_POST['token']??'')); ?>">
          <div class="form-group"><label class="form-label">New Password <span style="color:var(--stone-mid);font-weight:400;">(min. 6 characters)</span></label><input type="password" name="new_password" class="form-input" required autocomplete="new-password" minlength="6"></div>
          <div class="form-group"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-input" required autocomplete="new-password"></div>
          <button type="submit" class="btn btn-gold btn-full" style="margin-top:4px;">Reset Password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
