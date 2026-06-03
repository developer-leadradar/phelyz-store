<?php
$pageTitle = "My Addresses";
require_once 'includes/header.php';
requireLogin();
$user = getCurrentUser();
$db   = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $db->query("DELETE FROM addresses WHERE id=? AND user_id=?",[(int)$_GET['delete'],$_SESSION['user_id']]);
    redirect('customer-addresses.php?msg=deleted');
}
// Handle set default
if (isset($_GET['set_default'])) {
    $db->query("UPDATE addresses SET is_default=0 WHERE user_id=?",[$_SESSION['user_id']]);
    $db->query("UPDATE addresses SET is_default=1 WHERE id=? AND user_id=?",[(int)$_GET['set_default'],$_SESSION['user_id']]);
    redirect('customer-addresses.php?msg=default_set');
}
// Handle add
$success=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_address'])) {
    $data=['user_id'=>$_SESSION['user_id'],'type'=>'shipping','first_name'=>sanitize($_POST['first_name']),'last_name'=>sanitize($_POST['last_name']),'address'=>sanitize($_POST['address']),'city'=>sanitize($_POST['city']),'state'=>sanitize($_POST['state']??''),'zip_code'=>sanitize($_POST['zip_code']??''),'country'=>sanitize($_POST['country']??'Nigeria'),'phone'=>sanitize($_POST['phone']),'is_default'=>0];
    if ($db->insert('addresses',$data)) { $success='Address added.'; } else { $error='Could not save address.'; }
}

$addresses = $db->fetchAll("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, created_at DESC",[$_SESSION['user_id']]);

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
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
      <div>
        <div class="breadcrumb"><a href="customer-dashboard.php">Account</a><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg><span>Addresses</span></div>
        <h1 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);">My Addresses</h1>
      </div>
      <button onclick="document.getElementById('add-form').style.display=document.getElementById('add-form').style.display==='none'?'block':'none'" class="btn btn-gold btn-sm">+ Add New Address</button>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if (isset($_GET['msg'])&&$_GET['msg']==='deleted'): ?><div class="alert alert-success">Address removed.</div><?php endif; ?>

    <!-- Add address form (hidden by default) -->
    <div id="add-form" style="display:none;margin-bottom:24px;">
      <div class="card" style="padding:24px;">
        <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);margin-bottom:20px;">New Address</h3>
        <form method="POST">
          <input type="hidden" name="add_address" value="1">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div class="form-group" style="margin:0;"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-input" required value="<?php echo htmlspecialchars($user['first_name']); ?>"></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-input" required value="<?php echo htmlspecialchars($user['last_name']); ?>"></div>
          </div>
          <div class="form-group" style="margin-bottom:14px;"><label class="form-label">Street Address *</label><input type="text" name="address" class="form-input" required></div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">
            <div class="form-group" style="margin:0;"><label class="form-label">City *</label><input type="text" name="city" class="form-input" required></div>
            <div class="form-group" style="margin:0;"><label class="form-label">State</label><input type="text" name="state" class="form-input"></div>
            <div class="form-group" style="margin:0;"><label class="form-label">ZIP Code</label><input type="text" name="zip_code" class="form-input"></div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
            <div class="form-group" style="margin:0;"><label class="form-label">Phone *</label><input type="tel" name="phone" class="form-input" required></div>
            <div class="form-group" style="margin:0;"><label class="form-label">Country</label><input type="text" name="country" class="form-input" value="Nigeria"></div>
          </div>
          <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-gold">Save Address</button>
            <button type="button" onclick="document.getElementById('add-form').style.display='none'" class="btn btn-ghost">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Address grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
      <?php foreach ($addresses as $addr): ?>
        <div style="background:white;border:1.5px solid <?php echo $addr['is_default']?'var(--gold)':'var(--cream-dark)'; ?>;border-radius:14px;padding:20px;position:relative;">
          <?php if ($addr['is_default']): ?>
            <span style="position:absolute;top:14px;right:14px;background:var(--gold);color:white;font-size:10px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;padding:3px 8px;border-radius:99px;">Default</span>
          <?php endif; ?>
          <div style="margin-bottom:12px;">
            <div style="font-weight:700;color:var(--black);margin-bottom:6px;"><?php echo htmlspecialchars($addr['first_name'].' '.$addr['last_name']); ?></div>
            <div style="font-size:13px;color:var(--stone-mid);line-height:1.65;">
              <?php echo htmlspecialchars($addr['address']); ?><br>
              <?php echo htmlspecialchars($addr['city'].', '.($addr['state']??'').' '.($addr['zip_code']??'')); ?><br>
              <?php echo htmlspecialchars($addr['country']); ?><br>
              <?php echo htmlspecialchars($addr['phone']); ?>
            </div>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <?php if (!$addr['is_default']): ?>
              <a href="?set_default=<?php echo $addr['id']; ?>" style="font-size:12px;font-weight:600;color:var(--gold);">Set as Default</a>
              <span style="color:var(--cream-dark);">|</span>
            <?php endif; ?>
            <a href="?delete=<?php echo $addr['id']; ?>" onclick="return confirm('Delete this address?')" style="font-size:12px;font-weight:600;color:#EF4444;">Remove</a>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (empty($addresses)): ?>
        <div style="text-align:center;padding:40px;background:white;border:1.5px dashed var(--cream-dark);border-radius:14px;grid-column:1/-1;">
          <p style="font-size:14px;color:var(--stone-mid);margin-bottom:16px;">No saved addresses yet.</p>
          <button onclick="document.getElementById('add-form').style.display='block';document.getElementById('add-form').scrollIntoView({behavior:'smooth'})" class="btn btn-gold btn-sm">Add Your First Address</button>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
