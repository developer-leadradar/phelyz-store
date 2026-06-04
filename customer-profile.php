<?php
$pageTitle = "Profile & Security";
require_once 'includes/header.php';
requireLogin();
$user    = getCurrentUser();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = sanitize($_POST['first_name']);
    $lastName  = sanitize($_POST['last_name']);
    $phone     = sanitize($_POST['phone']);
    $db        = getDB();
    if ($db->update('users',['first_name'=>$firstName,'last_name'=>$lastName,'phone'=>$phone],'id = ?',[$_SESSION['user_id']]))
        { $success='Profile updated successfully.'; $user=getCurrentUser(); }
    else { $error='Failed to update profile.'; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    if (!password_verify($current,$user['password'])) { $error='Current password is incorrect.'; }
    elseif (strlen($new)<6) { $error='New password must be at least 6 characters.'; }
    elseif ($new!==$confirm) { $error='New passwords do not match.'; }
    else {
        $db=getDB(); $hashed=password_hash($new,PASSWORD_HASH_ALGO,['cost'=>PASSWORD_HASH_COST]);
        $db->update('users',['password'=>$hashed],'id = ?',[$_SESSION['user_id']]);
        $success='Password changed successfully.';
    }
}
$customerNav=[
  ['customer-dashboard.php','Dashboard','M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5'],
  ['customer-orders.php','My Orders','M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z'],
  ['customer-profile.php','Profile & Security','M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z'],
  ['customer-addresses.php','My Addresses','M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
  ['customer-wishlist.php','Wishlist','M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
  ['customer-orders.php?status=delivered','My Reviews','M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
  ['logout.php','Sign Out','M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75'],
];
$currentPage=basename($_SERVER['PHP_SELF']);
?>
<div class="customer-layout">
  <aside class="customer-sidebar">
    <div class="sidebar-user-block">
      <div class="sidebar-avatar"><?php echo strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)); ?></div>
      <div class="sidebar-name"><?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></div>
      <div class="sidebar-email"><?php echo htmlspecialchars($user['email']); ?></div>
    </div>
    <nav style="padding:8px 0;">
      <?php foreach ($customerNav as [$href,$label,$icon]): ?>
        <a href="<?php echo $href; ?>" class="sidebar-nav-link <?php echo $currentPage===$href?'active':''; ?>">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $icon; ?>"/></svg>
          <?php echo $label; ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <div>
    <div style="margin-bottom:24px;">
      <div class="breadcrumb"><a href="customer-dashboard.php">Account</a><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg><span>Profile & Security</span></div>
      <h1 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);">Profile & Security</h1>
    </div>
    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Personal Info -->
    <div class="card" style="padding:28px;margin-bottom:20px;">
      <h2 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--cream-dark);">Personal Information</h2>
      <form method="POST">
        <input type="hidden" name="update_profile" value="1">
        <div class="profile-form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
          <div class="form-group" style="margin:0;"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-input" required value="<?php echo htmlspecialchars($user['first_name']); ?>"></div>
          <div class="form-group" style="margin:0;"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-input" required value="<?php echo htmlspecialchars($user['last_name']); ?>"></div>
        </div>
        <div class="form-group" style="margin-bottom:16px;"><label class="form-label">Email Address <span style="color:var(--stone-mid);font-weight:400;">(cannot be changed)</span></label><input type="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity:0.6;cursor:not-allowed;"></div>
        <div class="form-group" style="margin-bottom:20px;"><label class="form-label">Phone Number</label><input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone']??''); ?>"></div>
        <button type="submit" class="btn btn-gold">Save Changes</button>
      </form>
    </div>

    <!-- Change Password -->
    <div class="card" style="padding:28px;">
      <h2 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--cream-dark);">Change Password</h2>
      <form method="POST">
        <input type="hidden" name="change_password" value="1">
        <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-input" required autocomplete="current-password"></div>
        <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-input" required autocomplete="new-password"><div class="form-hint">At least 6 characters</div></div>
        <div class="form-group"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-input" required autocomplete="new-password"></div>
        <button type="submit" class="btn btn-dark">Update Password</button>
      </form>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
