<?php
$pageTitle = "My Orders";
require_once 'includes/header.php';
requireLogin();
$user         = getCurrentUser();
$orders       = getOrdersByUser($_SESSION['user_id']);
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$customerNav  = [
  ['customer-dashboard.php','Dashboard','M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5'],
  ['customer-orders.php','My Orders','M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z'],
  ['customer-profile.php','Profile & Security','M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z'],
  ['customer-addresses.php','My Addresses','M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
  ['customer-wishlist.php','Wishlist','M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
  ['customer-orders.php?status=delivered','My Reviews','M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
  ['logout.php','Sign Out','M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75'],
];
$currentPage   = basename($_SERVER['PHP_SELF']);
$statusColors  = ['pending'=>'status-pending','processing'=>'status-processing','shipped'=>'status-shipped','delivered'=>'status-delivered','cancelled'=>'status-cancelled'];
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
      <div class="breadcrumb"><a href="customer-dashboard.php">Account</a><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg><span>My Orders</span></div>
      <h1 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);">My Orders</h1>
    </div>
    <div style="display:flex;gap:4px;border-bottom:1px solid var(--cream-dark);margin-bottom:24px;overflow-x:auto;">
      <?php foreach(['all'=>'All','pending'=>'Pending','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $val=>$label): ?>
        <a href="?status=<?php echo $val; ?>" style="padding:10px 14px;font-size:13px;font-weight:600;white-space:nowrap;border-bottom:2px solid transparent;color:var(--stone-mid);text-decoration:none;<?php echo $statusFilter===$val?'border-bottom-color:var(--gold);color:var(--black);':''; ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </div>
    <?php
    $filtered = array_filter($orders, fn($o)=>$statusFilter==='all'||$o['status']===$statusFilter);
    if (empty($filtered)): ?>
      <div style="text-align:center;padding:60px 20px;background:white;border-radius:16px;border:1px solid var(--cream-dark);">
        <h3 style="font-family:'Cormorant',serif;font-size:22px;font-weight:700;color:var(--black);margin-bottom:8px;">No orders found</h3>
        <p style="font-size:14px;color:var(--stone-mid);margin-bottom:20px;">You haven't placed any orders yet.</p>
        <a href="shop.php" class="btn btn-gold">Start Shopping</a>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:16px;">
        <?php foreach ($filtered as $order): $items = getOrderItems($order['id']); ?>
          <div class="card" style="padding:0;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:var(--cream);border-bottom:1px solid var(--cream-dark);flex-wrap:wrap;gap:10px;">
              <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <div><div style="font-size:10px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--stone-mid);">Order #</div><div style="font-size:13px;font-weight:700;color:var(--black);"><?php echo htmlspecialchars($order['order_number']); ?></div></div>
                <div><div style="font-size:10px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--stone-mid);">Date</div><div style="font-size:13px;font-weight:600;color:var(--black);"><?php echo formatDate($order['created_at']); ?></div></div>
                <div><div style="font-size:10px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--stone-mid);">Total</div><div style="font-size:13px;font-weight:700;color:var(--black);"><?php echo formatPrice($order['total']); ?></div></div>
              </div>
              <div style="display:flex;align-items:center;gap:10px;">
                <span class="status-badge <?php echo $statusColors[$order['status']]??'status-pending'; ?>"><?php echo ucfirst($order['status']); ?></span>
                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">View</a>
              </div>
            </div>
            <!-- Items row -->
            <div style="padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
              <?php foreach (array_slice($items,0,4) as $item): ?>
                <div style="display:flex;align-items:center;gap:12px;">
                  <div style="width:52px;height:52px;border-radius:10px;overflow:hidden;flex-shrink:0;border:1px solid var(--cream-dark);background:var(--cream);">
                    <img src="<?php echo htmlspecialchars($item['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($item['product_name'] ?? ''); ?>"
                         style="width:100%;height:100%;object-fit:cover;"
                         onerror="this.src='https://placehold.co/52x52/F5F5F4/78716C?text=J'">
                  </div>
                  <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;color:var(--black);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                      <?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?>
                    </div>
                    <div style="font-size:12px;color:var(--stone-mid);margin-top:2px;">
                      Qty <?php echo $item['quantity']; ?> &nbsp;·&nbsp; <?php echo formatPrice($item['price_at_purchase'] ?? $item['price'] ?? 0); ?>
                    </div>
                  </div>
                  <div style="font-size:13px;font-weight:700;color:var(--black);flex-shrink:0;">
                    <?php echo formatPrice(($item['price_at_purchase'] ?? $item['price'] ?? 0) * $item['quantity']); ?>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if(count($items)>4): ?>
                <div style="font-size:12px;color:var(--stone-mid);padding-left:64px;">+<?php echo count($items)-4; ?> more item<?php echo count($items)-4>1?'s':''; ?></div>
              <?php endif; ?>
            </div>
            <!-- Review strip for delivered orders -->
            <?php if (in_array($order['status'], ['delivered', 'completed'])): ?>
              <div style="padding:12px 20px;border-top:1px solid var(--cream-dark);background:rgba(202,138,4,0.04);display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span style="font-size:11px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--stone-mid);margin-right:4px;">Rate your items:</span>
                <?php foreach ($items as $item): ?>
                  <a href="product.php?id=<?php echo $item['product_id']; ?>#reviews"
                     style="font-size:12px;font-weight:600;color:var(--gold);background:white;border:1.5px solid var(--gold);border-radius:20px;padding:5px 14px;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all 0.2s;white-space:nowrap;"
                     onmouseover="this.style.background='rgba(202,138,4,0.10)'" onmouseout="this.style.background='white'">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="12" height="12"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <?php echo htmlspecialchars(mb_strimwidth($item['product_name'] ?? '',0,20,'…')); ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
