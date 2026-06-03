<?php
$pageTitle = "Sign In";
require_once 'includes/header.php';
require_once 'includes/cart-functions.php';

if (isLoggedIn()) redirect('customer-dashboard.php');

$error    = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'customer-dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (login($email, $password)) {
        if (isAdmin()) {
            redirect('admin/index.php');
        }
        mergeGuestCart($_SESSION['user_id']);
        redirect($redirect);
    } else {
        $error = 'Incorrect email or password. Please try again.';
    }
}
?>

<div class="auth-split">
  <!-- Left image panel -->
  <div class="auth-panel-left">
    <img src="https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=700&h=900&fit=crop&q=80" alt="Luxury diamond jewelry">
    <div class="auth-panel-left-inner">
      <div style="font-family:'Cormorant',serif;font-size:36px;font-weight:700;color:white;margin-bottom:12px;letter-spacing:0.06em;">PHELYZ</div>
      <p style="font-size:15px;color:rgba(255,255,255,0.65);line-height:1.7;max-width:300px;margin-bottom:20px;">Certified fine jewelry crafted to celebrate life's most precious moments.</p>
      <?php foreach(['Certified authentic diamonds','Free shipping on ₦50,000+','30-day hassle-free returns'] as $f): ?>
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,0.70);margin-bottom:8px;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="#CA8A04" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <?php echo $f; ?>
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
      <h1 class="auth-heading">Welcome back</h1>
      <p class="auth-sub">Sign in to your account to continue shopping</p>

      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-input"
                 placeholder="your@email.com" required autocomplete="email"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div style="position:relative;">
            <input type="password" id="password" name="password" class="form-input"
                   placeholder="••••••••" required autocomplete="current-password" style="padding-right:44px;">
            <button type="button" onclick="var i=document.getElementById('password');i.type=i.type==='password'?'text':'password';"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--stone-mid);cursor:pointer;">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </button>
          </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--stone);">
            <input type="checkbox" name="remember" style="accent-color:var(--gold);width:14px;height:14px;"> Remember me
          </label>
          <a href="forgot-password.php" style="font-size:13px;color:var(--gold);font-weight:600;">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-gold btn-full" style="font-size:15px;padding:14px;">Sign In to Your Account</button>
      </form>

      <div class="auth-divider">or</div>
      <p style="text-align:center;font-size:14px;color:var(--stone-mid);">
        Don't have an account? <a href="register.php" style="color:var(--gold);font-weight:700;margin-left:4px;">Create one now →</a>
      </p>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
