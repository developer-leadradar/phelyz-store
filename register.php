<?php
$pageTitle = "Create Account";
require_once 'includes/header.php';

if (isLoggedIn()) redirect('customer-dashboard.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName       = sanitize($_POST['first_name']);
    $lastName        = sanitize($_POST['last_name']);
    $email           = sanitize($_POST['email']);
    $phone           = sanitize($_POST['phone']);
    $password        = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($firstName)||empty($lastName)||empty($email)||empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $userData = ['first_name'=>$firstName,'last_name'=>$lastName,'email'=>$email,'phone'=>$phone,'password'=>$password,'role'=>'customer','is_active'=>0];
        $result   = register($userData);

        if ($result['success']) {
            $db        = getDB();
            $userId    = $result['user_id'];
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $db->insert('email_verifications', ['user_id'=>$userId,'email'=>$email,'token'=>$token,'expires_at'=>$expiresAt]);
            $verifyLink = SITE_URL . '/verify-email.php?token=' . $token;
            $subject = 'Verify Your Phelyz Store Email Address';
            $message = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px;"><tr><td align="center"><table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);"><tr><td style="background:#1C1917;padding:36px 40px;text-align:center;"><h1 style="color:#CA8A04;font-size:26px;font-weight:800;letter-spacing:3px;margin:0;">PHELYZ</h1><p style="color:rgba(255,255,255,0.6);font-size:13px;margin:6px 0 0;">Confirm Your Email Address</p></td></tr><tr><td style="padding:40px;"><p style="color:#1C1917;font-size:16px;margin:0 0 12px;">Hello <strong>'.htmlspecialchars($firstName).'</strong>,</p><p style="color:#44403C;font-size:15px;line-height:1.7;margin:0 0 28px;">Welcome to Phelyz Store! Please verify your email to activate your account. This link expires in 24 hours.</p><div style="text-align:center;margin:32px 0;"><a href="'.$verifyLink.'" style="display:inline-block;background:#CA8A04;color:#ffffff;text-decoration:none;padding:16px 40px;border-radius:8px;font-size:16px;font-weight:700;">Verify My Email</a></div><p style="color:#78716C;font-size:13px;margin:0 0 8px;">Or copy this link:</p><p style="background:#f8f9fb;border:1px solid #e4e8ef;border-radius:6px;padding:12px;font-size:12px;color:#44403C;word-break:break-all;margin:0 0 28px;">'.$verifyLink.'</p></td></tr><tr><td style="background:#f8f9fb;border-top:1px solid #e4e8ef;padding:20px 40px;text-align:center;"><p style="color:#78716C;font-size:12px;margin:0;">&copy; '.date('Y').' Phelyz Store &middot; Lagos, Nigeria</p></td></tr></table></td></tr></table></body></html>';
            sendEmail($email, $subject, $message);
            $success = 'Account created! Check your email and click the verification link to activate your account.';
        } else {
            $error = $result['message'] ?? 'This email address is already registered. Please sign in.';
        }
    }
}
?>

<div class="auth-split">
  <!-- Left panel -->
  <div class="auth-panel-left">
    <img src="https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=700&h=900&fit=crop&q=80" alt="Fine jewelry craftsmanship">
    <div class="auth-panel-left-inner">
      <div style="font-family:'Cormorant',serif;font-size:36px;font-weight:700;color:white;margin-bottom:12px;letter-spacing:0.06em;">PHELYZ</div>
      <p style="font-size:15px;color:rgba(255,255,255,0.65);line-height:1.7;max-width:300px;margin-bottom:20px;">Join thousands of customers who trust us for their most special moments.</p>
      <?php foreach(['Track orders in real-time','Save items to your wishlist','Exclusive member-only offers','Faster checkout experience'] as $b): ?>
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,0.70);margin-bottom:8px;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="#CA8A04" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <?php echo $b; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right form panel -->
  <div class="auth-panel-right">
    <div class="auth-form-inner">
      <div class="auth-logo-text">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M6.5 2h11l4 6-9.5 14L2.5 8l4-6z"/></svg>
        PHELYZ
      </div>
      <h1 class="auth-heading">Create account</h1>
      <p class="auth-sub">Join Phelyz Store and discover fine jewelry</p>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <?php echo htmlspecialchars($success); ?>
        </div>
        <p style="text-align:center;margin-top:16px;"><a href="login.php" class="btn btn-gold">Sign In Now</a></p>
      <?php else: ?>

      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:0;">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" name="first_name" class="form-input" required
                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Last Name *</label>
            <input type="text" name="last_name" class="form-input" required
                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-input" required autocomplete="email"
                 placeholder="your@email.com"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="tel" name="phone" class="form-input" placeholder="+234 000 000 0000"
                 value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Password * <span style="font-weight:400;color:var(--stone-mid);">(min. 6 characters)</span></label>
          <div style="position:relative;">
            <input type="password" id="pwd" name="password" class="form-input" required autocomplete="new-password" style="padding-right:44px;">
            <button type="button" onclick="var i=document.getElementById('pwd');i.type=i.type==='password'?'text':'password';"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--stone-mid);cursor:pointer;">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password *</label>
          <input type="password" name="confirm_password" class="form-input" required autocomplete="new-password">
        </div>
        <div style="margin-bottom:20px;">
          <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:13px;color:var(--stone);">
            <input type="checkbox" required style="accent-color:var(--gold);width:14px;height:14px;margin-top:2px;flex-shrink:0;">
            <span>I agree to Phelyz's <a href="#" style="color:var(--gold);">Terms &amp; Conditions</a> and <a href="#" style="color:var(--gold);">Privacy Policy</a></span>
          </label>
        </div>
        <button type="submit" class="btn btn-gold btn-full" style="font-size:15px;padding:14px;">Create My Account</button>
      </form>

      <div class="auth-divider">or</div>
      <p style="text-align:center;font-size:14px;color:var(--stone-mid);">
        Already have an account? <a href="login.php" style="color:var(--gold);font-weight:700;margin-left:4px;">Sign in →</a>
      </p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
