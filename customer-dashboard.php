<?php
$pageTitle = "My Account";
require_once 'includes/header.php';
requireLogin();
$user = getCurrentUser();

$customerNav = [
  ['customer-dashboard.php','Dashboard','M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5'],
  ['customer-orders.php','My Orders','M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z'],
  ['customer-profile.php','Profile & Security','M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z'],
  ['customer-addresses.php','My Addresses','M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
  ['customer-wishlist.php','Wishlist','M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
  ['customer-orders.php?status=delivered#reviews','My Reviews','M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
  ['logout.php','Sign Out','M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75'],
];
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="customer-layout">
  <!-- Sidebar -->
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

  <!-- Main content -->
  <div>
    <div style="margin-bottom:28px;">
      <h1 style="font-family:'Cormorant',serif;font-size:30px;font-weight:700;color:var(--black);margin-bottom:4px;">Hello, <?php echo htmlspecialchars($user['first_name']); ?></h1>
      <p style="font-size:14px;color:var(--stone-mid);">Welcome to your account. What would you like to do today?</p>
    </div>

    <!-- Dashboard cards -->
    <div class="dashboard-cards-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;">
      <?php
      $cards = [
        ['customer-orders.php','My Orders','Track, return, or reorder','M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z','rgba(202,138,4,0.10)','var(--gold)'],
        ['customer-profile.php','Profile & Security','Edit name, email & password','M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z','rgba(59,130,246,0.10)','#3B82F6'],
        ['customer-addresses.php','My Addresses','Manage delivery addresses','M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z','rgba(16,185,129,0.10)','#10B981'],
        ['customer-wishlist.php','My Wishlist','View your saved items','M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z','rgba(239,68,68,0.10)','#EF4444'],
        ['customer-orders.php?status=delivered','My Reviews','Review purchased items','M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z','rgba(234,179,8,0.10)','#EAB308'],
        ['contact.php','Customer Service','Get help & support','M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z','rgba(139,92,246,0.10)','#8B5CF6'],
        ['logout.php','Sign Out','Log out of your account','M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75','rgba(107,114,128,0.10)','#6B7280'],
      ];
      foreach ($cards as [$href,$title,$sub,$icon,$iconBg,$iconColor]):
      ?>
        <a href="<?php echo $href; ?>"
           style="display:flex;flex-direction:column;gap:14px;padding:22px;background:white;border:1.5px solid var(--cream-dark);border-radius:16px;transition:all 0.2s;cursor:pointer;text-decoration:none;"
           onmouseover="this.style.borderColor='var(--gold)';this.style.boxShadow='var(--shadow-md)';this.style.transform='translateY(-3px)'"
           onmouseout="this.style.borderColor='var(--cream-dark)';this.style.boxShadow='none';this.style.transform='none'">
          <div style="width:46px;height:46px;border-radius:12px;background:<?php echo $iconBg; ?>;display:flex;align-items:center;justify-content:center;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="<?php echo $iconColor; ?>" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $icon; ?>"/></svg>
          </div>
          <div>
            <div style="font-weight:700;font-size:14px;color:var(--black);margin-bottom:4px;"><?php echo $title; ?></div>
            <div style="font-size:12px;color:var(--stone-mid);"><?php echo $sub; ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
