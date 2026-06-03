<?php
$pageTitle = "My Account";
require_once 'includes/header.php';
requireLogin();
$user      = getCurrentUser();
$cartCount = getCartCount();
?>

<div style="background:var(--cream);min-height:calc(100vh - var(--nav-height));padding:0 0 60px;">

  <!-- ── Welcome Banner ──────────────────────────────── -->
  <div style="background:linear-gradient(135deg,var(--black) 0%,var(--stone) 100%);padding:36px 0;position:relative;overflow:hidden;">
    <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 80% at 80% 50%,rgba(202,138,4,0.15),transparent);pointer-events:none;"></div>
    <div class="container" style="position:relative;z-index:2;">
      <!-- Top row: avatar + info side by side always -->
      <div style="display:flex;align-items:center;gap:16px;flex-wrap:nowrap;">
        <!-- Avatar -->
        <div style="width:58px;height:58px;border-radius:50%;background:rgba(202,138,4,0.20);border:2px solid rgba(202,138,4,0.40);display:flex;align-items:center;justify-content:center;font-family:'Cormorant',serif;font-size:24px;font-weight:700;color:var(--gold);flex-shrink:0;">
          <?php echo strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)); ?>
        </div>
        <!-- Info -->
        <div style="flex:1;min-width:0;overflow:hidden;">
          <p style="font-size:11px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:rgba(202,138,4,0.80);margin-bottom:2px;">Member Account</p>
          <h1 style="font-family:'Cormorant',serif;font-size:clamp(20px,5vw,32px);font-weight:700;color:white;margin-bottom:2px;line-height:1.15;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            Hello, <?php echo htmlspecialchars($user['first_name']); ?> <?php echo htmlspecialchars($user['last_name']); ?>
          </h1>
          <p style="font-size:12px;color:rgba(255,255,255,0.50);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
      </div>
      <!-- Cart badge — full row below on all screen sizes -->
      <?php if ($cartCount > 0): ?>
      <div style="margin-top:16px;">
        <a href="cart.php" style="display:inline-flex;align-items:center;gap:8px;background:var(--gold);color:var(--black);padding:10px 20px;border-radius:99px;font-size:13px;font-weight:700;text-decoration:none;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
          <?php echo $cartCount; ?> item<?php echo $cartCount!=1?'s':''; ?> in cart
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Quick Action Cards ───────────────────────────── -->
  <div class="container" style="padding-top:32px;">
    <p style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--stone-mid);margin-bottom:16px;">What would you like to do?</p>

    <div class="dashboard-cards-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;">
      <?php
      $cards = [
        ['cart.php','My Cart','View &amp; manage your cart','M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm5.625 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z','rgba(202,138,4,0.10)','var(--gold)'],
        ['customer-orders.php','My Orders','Track, return, or reorder','M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2','rgba(59,130,246,0.10)','#3B82F6'],
        ['customer-profile.php','Profile &amp; Security','Edit name, email &amp; password','M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z','rgba(59,130,246,0.10)','#3B82F6'],
        ['customer-addresses.php','My Addresses','Manage delivery addresses','M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z','rgba(16,185,129,0.10)','#10B981'],
        ['customer-wishlist.php','My Wishlist','View your saved items','M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z','rgba(239,68,68,0.10)','#EF4444'],
        ['customer-orders.php?status=delivered','My Reviews','Rate purchased items','M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z','rgba(234,179,8,0.10)','#EAB308'],
        ['contact.php','Customer Service','Get help &amp; support','M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z','rgba(139,92,246,0.10)','#8B5CF6'],
        ['logout.php','Sign Out','Log out of your account','M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75','rgba(107,114,128,0.10)','#6B7280'],
      ];
      foreach ($cards as [$href,$title,$sub,$icon,$iconBg,$iconColor]):
      ?>
        <a href="<?php echo $href; ?>"
           style="display:flex;flex-direction:column;gap:14px;padding:20px;background:white;border:1.5px solid var(--cream-dark);border-radius:16px;transition:all 0.2s;cursor:pointer;text-decoration:none;"
           onmouseover="this.style.borderColor='var(--gold)';this.style.boxShadow='var(--shadow-md)';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.borderColor='var(--cream-dark)';this.style.boxShadow='none';this.style.transform='none'">
          <div style="width:44px;height:44px;border-radius:12px;background:<?php echo $iconBg; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="<?php echo $iconColor; ?>" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $icon; ?>"/></svg>
          </div>
          <div>
            <div style="font-weight:700;font-size:13px;color:var(--black);margin-bottom:3px;"><?php echo $title; ?></div>
            <div style="font-size:11px;color:var(--stone-mid);line-height:1.4;"><?php echo $sub; ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
