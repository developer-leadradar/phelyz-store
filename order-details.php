<?php
$pageTitle = "Order Details";
require_once 'includes/header.php';
requireLogin();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$orderId) redirect('customer-orders.php');

$order = getOrderById($orderId);
if (!$order || $order['user_id'] != $_SESSION['user_id']) redirect('customer-orders.php');

$orderItems = getOrderItems($orderId);
$user       = getCurrentUser();
$success    = isset($_GET['success']) && $_GET['success'] == '1';
$statusColors = ['pending'=>'status-pending','processing'=>'status-processing','shipped'=>'status-shipped','delivered'=>'status-delivered','cancelled'=>'status-cancelled'];
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
    <?php if ($success): ?>
      <div class="alert alert-success" style="margin-bottom:20px;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div><strong>Order placed successfully!</strong> Thank you for shopping with Phelyz Store. We'll process your order shortly.</div>
      </div>
    <?php endif; ?>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
      <div>
        <div class="breadcrumb"><a href="customer-dashboard.php">Account</a><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg><a href="customer-orders.php">Orders</a><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg><span><?php echo htmlspecialchars($order['order_number']); ?></span></div>
        <h1 style="font-family:'Cormorant',serif;font-size:26px;font-weight:700;color:var(--black);">Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
      </div>
      <span class="status-badge <?php echo $statusColors[$order['status']]??'status-pending'; ?>" style="font-size:12px;"><?php echo ucfirst($order['status']); ?></span>
    </div>

    <!-- Order meta -->
    <div class="card" style="padding:20px;margin-bottom:20px;">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;">
        <div><div style="font-size:10px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;">Date Placed</div><div style="font-size:13px;font-weight:600;"><?php echo formatDate($order['created_at']); ?></div></div>
        <div><div style="font-size:10px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;">Payment Method</div><div style="font-size:13px;font-weight:600;"><?php
$pmLabels = ['cod'=>'Cash on Delivery','bank_transfer'=>'Bank Transfer','card'=>'Card Payment'];
echo htmlspecialchars($pmLabels[$order['payment_method']] ?? ucwords(str_replace('_',' ',$order['payment_method'])));
?></div></div>
        <div><div style="font-size:10px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;">Ship To</div><div style="font-size:13px;font-weight:600;"><?php echo htmlspecialchars($order['shipping_first_name'].' '.$order['shipping_last_name']); ?></div></div>
        <div><div style="font-size:10px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;">Order Total</div><div style="font-size:16px;font-family:'Cormorant',serif;font-weight:700;color:var(--gold);"><?php echo formatPrice($order['total']); ?></div></div>
      </div>
    </div>

    <!-- Order items -->
    <div class="card" style="padding:0;overflow:hidden;margin-bottom:20px;">
      <div style="padding:16px 20px;border-bottom:1px solid var(--cream-dark);display:flex;align-items:center;justify-content:space-between;">
        <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);">Items Ordered</h3>
        <span style="font-size:13px;color:var(--stone-mid);"><?php echo count($orderItems); ?> item<?php echo count($orderItems)!=1?'s':''; ?></span>
      </div>
      <?php foreach ($orderItems as $item): ?>
        <div style="display:flex;align-items:center;gap:14px;padding:16px 20px;border-bottom:1px solid var(--cream-dark);">
          <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:10px;flex-shrink:0;" onerror="this.src='https://placehold.co/64x64/F5F5F4/78716C?text=J'">
          <div style="flex:1;">
            <div style="font-size:14px;font-weight:600;color:var(--black);margin-bottom:3px;"><?php echo htmlspecialchars($item['product_name'] ?? ''); ?></div>
            <div style="font-size:12px;color:var(--stone-mid);">Qty: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price_at_purchase'] ?? $item['price'] ?? 0); ?></div>
          </div>
          <div style="font-size:14px;font-weight:700;color:var(--black);"><?php echo formatPrice(($item['price_at_purchase'] ?? $item['price'] ?? 0) * $item['quantity']); ?></div>
        </div>
      <?php endforeach; ?>
      <div style="padding:16px 20px;">
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;"><span style="color:var(--stone-mid);">Subtotal</span><span style="font-weight:600;"><?php echo formatPrice($order['subtotal']); ?></span></div>
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;"><span style="color:var(--stone-mid);">Shipping</span><span style="font-weight:600;color:<?php echo $order['shipping']==0?'#22C55E':'var(--black)'; ?>"><?php echo $order['shipping']==0?'FREE':formatPrice($order['shipping']); ?></span></div>
        <?php if (!empty($order['tax']) && $order['tax']>0): ?><div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;"><span style="color:var(--stone-mid);">Tax</span><span style="font-weight:600;"><?php echo formatPrice($order['tax']); ?></span></div><?php endif; ?>
        <div style="display:flex;justify-content:space-between;padding-top:12px;border-top:2px solid var(--black);"><span style="font-weight:700;">Total</span><span style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);"><?php echo formatPrice($order['total']); ?></span></div>
      </div>
    </div>

    <!-- Shipping address -->
    <div class="card" style="padding:20px;">
      <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);margin-bottom:14px;">Shipping Address</h3>
      <div style="font-size:14px;color:var(--stone-mid);line-height:1.70;">
        <?php echo htmlspecialchars($order['shipping_first_name'].' '.$order['shipping_last_name']); ?><br>
        <?php echo htmlspecialchars($order['shipping_address']); ?><br>
        <?php echo htmlspecialchars($order['shipping_city']); ?><br>
        Phone: <?php echo htmlspecialchars($order['shipping_phone']); ?>
      </div>
    </div>

    <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap;">
      <a href="customer-orders.php" class="btn btn-outline btn-sm">← Back to Orders</a>
      <a href="shop.php" class="btn btn-gold btn-sm">Continue Shopping</a>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
