<?php
$pageTitle = "Dashboard";
require_once 'includes/header.php';

$db = getDB();
$totalProducts      = $db->fetchOne("SELECT COUNT(*) as total FROM products")['total'];
$totalOrders        = $db->fetchOne("SELECT COUNT(*) as total FROM orders")['total'];
$totalCustomers     = $db->fetchOne("SELECT COUNT(*) as total FROM users WHERE role = 'customer'")['total'];
$totalRevenue       = $db->fetchOne("SELECT SUM(total) as revenue FROM orders WHERE status != 'cancelled'")['revenue'] ?? 0;
$pendingOrders      = $db->fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'")['total'];
$lowStockProducts   = $db->fetchOne("SELECT COUNT(*) as total FROM products WHERE stock_quantity < 5 AND stock_quantity > 0")['total'];
$outOfStockProducts = $db->fetchOne("SELECT COUNT(*) as total FROM products WHERE stock_quantity = 0")['total'];
$recentOrders       = $db->fetchAll("SELECT o.*, u.first_name, u.last_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10");
$topProducts        = $db->fetchAll("SELECT p.id, p.name, p.image, p.price, SUM(oi.quantity) as total_sold, SUM(oi.subtotal) as revenue FROM products p JOIN order_items oi ON p.id = oi.product_id GROUP BY p.id ORDER BY total_sold DESC LIMIT 5");
$lowStock           = $db->fetchAll("SELECT * FROM products WHERE stock_quantity < 5 AND stock_quantity > 0 ORDER BY stock_quantity ASC LIMIT 5");
$statusColors       = ['pending'=>'status-pending','processing'=>'status-processing','shipped'=>'status-shipped','delivered'=>'status-delivered','cancelled'=>'status-cancelled'];
?>

<!-- KPI cards -->
<div class="admin-kpi-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
  <?php
  $kpis = [
    [formatPrice($totalRevenue),'Total Revenue','M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75','rgba(202,138,4,0.12)','var(--gold)'],
    [$totalOrders,'Total Orders','M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z','rgba(59,130,246,0.12)','#3B82F6'],
    [$totalCustomers,'Customers','M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z','rgba(16,185,129,0.12)','#10B981'],
    [$totalProducts,'Products','M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z','rgba(139,92,246,0.12)','#8B5CF6'],
  ];
  foreach ($kpis as [$val,$label,$icon,$bg,$color]): ?>
    <div class="stat-card">
      <div class="stat-icon-box" style="background:<?php echo $bg; ?>;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="<?php echo $color; ?>" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $icon; ?>"/></svg>
      </div>
      <div class="stat-number"><?php echo $val; ?></div>
      <div class="stat-label"><?php echo $label; ?></div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Alert cards -->
<div class="admin-alert-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;">
  <?php
  $alerts = [
    [$pendingOrders,'Pending Orders','Require action','#F59E0B','rgba(245,158,11,0.10)','orders.php?status=pending'],
    [$lowStockProducts,'Low Stock','Below 5 units','#EF4444','rgba(239,68,68,0.10)','products.php?filter=low_stock'],
    [$outOfStockProducts,'Out of Stock','Need restocking','#6B7280','rgba(107,114,128,0.10)','products.php?filter=out_of_stock'],
  ];
  foreach ($alerts as [$n,$t,$s,$c,$bg,$link]): ?>
    <a href="<?php echo $link; ?>" style="display:flex;align-items:center;gap:16px;padding:18px 20px;background:white;border:1px solid #E9ECEF;border-radius:12px;border-left:3px solid <?php echo $c; ?>;text-decoration:none;transition:box-shadow 0.2s;"
       onmouseover="this.style.boxShadow='0 4px 16px rgba(28,25,23,0.08)'" onmouseout="this.style.boxShadow='none'">
      <div style="width:42px;height:42px;border-radius:10px;background:<?php echo $bg; ?>;display:flex;align-items:center;justify-content:center;font-family:'Cormorant',serif;font-size:22px;font-weight:700;color:<?php echo $c; ?>;flex-shrink:0;"><?php echo $n; ?></div>
      <div><div style="font-size:14px;font-weight:700;color:var(--black);"><?php echo $t; ?></div><div style="font-size:12px;color:var(--stone-mid);"><?php echo $s; ?></div></div>
    </a>
  <?php endforeach; ?>
</div>

<!-- Content grid -->
<div class="admin-content-grid" style="display:grid;grid-template-columns:1fr 340px;gap:20px;margin-bottom:28px;">

  <!-- Recent orders table -->
  <div style="background:white;border:1px solid #E9ECEF;border-radius:14px;overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #E9ECEF;">
      <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);">Recent Orders</h3>
      <a href="orders.php" style="font-size:12px;font-weight:600;color:var(--gold);text-decoration:none;">View all →</a>
    </div>
    <?php if (empty($recentOrders)): ?>
      <p style="padding:32px;text-align:center;color:var(--stone-mid);font-size:14px;">No orders yet.</p>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($recentOrders as $o): ?>
              <tr>
                <td style="font-weight:600;color:var(--black);"><?php echo htmlspecialchars($o['order_number']); ?></td>
                <td><?php echo htmlspecialchars(($o['first_name']??'Guest').' '.($o['last_name']??'')); ?></td>
                <td style="color:var(--stone-mid);"><?php echo formatDate($o['created_at']); ?></td>
                <td style="font-weight:700;"><?php echo formatPrice($o['total']); ?></td>
                <td><span class="status-badge <?php echo $statusColors[$o['status']]??'status-pending'; ?>"><?php echo ucfirst($o['status']); ?></span></td>
                <td><a href="order-details.php?id=<?php echo $o['id']; ?>" style="font-size:12px;font-weight:600;color:var(--gold);">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Top products -->
  <div style="background:white;border:1px solid #E9ECEF;border-radius:14px;overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #E9ECEF;">
      <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);">Top Products</h3>
      <a href="products.php" style="font-size:12px;font-weight:600;color:var(--gold);text-decoration:none;">All →</a>
    </div>
    <div style="padding:0 8px 8px;">
      <?php if (empty($topProducts)): ?>
        <p style="padding:24px;text-align:center;color:var(--stone-mid);font-size:14px;">No sales data yet.</p>
      <?php else: ?>
        <?php foreach ($topProducts as $p): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:12px 12px;border-bottom:1px solid var(--cream-dark);">
            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px;flex-shrink:0;" onerror="this.src='https://placehold.co/40x40/F5F5F4/78716C?text=J'">
            <div style="flex:1;min-width:0;">
              <div style="font-size:13px;font-weight:600;color:var(--black);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($p['name']); ?></div>
              <div style="font-size:11px;color:var(--stone-mid);"><?php echo (int)$p['total_sold']; ?> sold · <?php echo formatPrice($p['revenue']); ?></div>
            </div>
            <a href="edit-product.php?id=<?php echo $p['id']; ?>" style="font-size:11px;color:var(--gold);font-weight:600;flex-shrink:0;">Edit</a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Low stock warning -->
<?php if (!empty($lowStock)): ?>
<div style="background:white;border:1px solid #E9ECEF;border-radius:14px;overflow:hidden;">
  <div style="display:flex;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid #E9ECEF;background:#FFFBEB;">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#F59E0B" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
    <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);">Low Stock Alert</h3>
  </div>
  <div style="overflow-x:auto;">
    <table class="data-table">
      <thead><tr><th>Product</th><th>SKU</th><th>Stock</th><th>Price</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($lowStock as $p): ?>
          <tr>
            <td style="font-weight:600;color:var(--black);"><?php echo htmlspecialchars($p['name']); ?></td>
            <td style="color:var(--stone-mid);"><?php echo htmlspecialchars($p['sku']??'—'); ?></td>
            <td><span style="font-weight:700;color:#EF4444;"><?php echo (int)$p['stock_quantity']; ?> left</span></td>
            <td><?php echo formatPrice($p['price']); ?></td>
            <td><a href="edit-product.php?id=<?php echo $p['id']; ?>" class="btn btn-gold btn-sm">Restock</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
