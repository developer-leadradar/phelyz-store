<?php
$pageTitle = "Orders";
require_once 'includes/header.php';

$db           = getDB();
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$searchQuery  = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$sql    = "SELECT o.*, u.first_name, u.last_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];
if ($statusFilter !== 'all') { $sql .= " AND o.status = ?"; $params[] = $statusFilter; }
if ($searchQuery) {
    $sql .= " AND (o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $t = '%'.$searchQuery.'%'; $params = array_merge($params,[$t,$t,$t,$t]);
}
$page    = max(1,(int)($_GET['page']??1));
$perPage = 20;
$offset  = ($page-1)*$perPage;
$countSql = str_replace("o.*, u.first_name, u.last_name, u.email","COUNT(*) as total",$sql);
$total    = $db->fetchOne($countSql,$params)['total']??0;
$totalPages = ceil($total/$perPage);
$sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage; $params[] = $offset;
$orders = $db->fetchAll($sql,$params);
$statusColors=['pending'=>'status-pending','processing'=>'status-processing','shipped'=>'status-shipped','delivered'=>'status-delivered','cancelled'=>'status-cancelled'];
?>

<!-- Filters bar -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
  <div style="display:flex;gap:4px;flex-wrap:wrap;">
    <?php foreach(['all'=>'All','pending'=>'Pending','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $val=>$label): ?>
      <a href="?status=<?php echo $val; ?><?php echo $searchQuery?'&search='.urlencode($searchQuery):''; ?>"
         style="padding:8px 14px;font-size:13px;font-weight:600;border-radius:8px;text-decoration:none;<?php echo $statusFilter===$val?'background:var(--black);color:white;':'background:white;color:var(--stone);border:1px solid #E9ECEF;'; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
  </div>
  <form method="GET" style="display:flex;gap:8px;">
    <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
    <input type="text" name="search" class="form-input" placeholder="Search orders…" value="<?php echo htmlspecialchars($searchQuery); ?>" style="width:220px;padding:8px 14px;font-size:13px;">
    <button type="submit" class="btn btn-dark btn-sm">Search</button>
  </form>
</div>

<div style="background:white;border:1px solid #E9ECEF;border-radius:14px;overflow:hidden;">
  <div style="padding:16px 20px;border-bottom:1px solid #E9ECEF;display:flex;align-items:center;justify-content:space-between;">
    <span style="font-size:14px;font-weight:600;color:var(--stone-mid);"><?php echo number_format($total); ?> order<?php echo $total!=1?'s':''; ?></span>
  </div>
  <?php if (empty($orders)): ?>
    <div style="text-align:center;padding:48px;color:var(--stone-mid);">No orders found.</div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td style="font-weight:700;color:var(--black);"><?php echo htmlspecialchars($o['order_number']); ?></td>
              <td>
                <div style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars(($o['first_name']??'Guest').' '.($o['last_name']??'')); ?></div>
                <div style="font-size:11px;color:var(--stone-mid);"><?php echo htmlspecialchars($o['email']??''); ?></div>
              </td>
              <td style="color:var(--stone-mid);font-size:12px;"><?php echo formatDate($o['created_at']); ?></td>
              <td style="color:var(--stone-mid);">—</td>
              <td style="font-weight:700;"><?php echo formatPrice($o['total']); ?></td>
              <td style="font-size:12px;color:var(--stone-mid);"><?php echo ucwords(str_replace('_',' ',$o['payment_method'])); ?></td>
              <td><span class="status-badge <?php echo $statusColors[$o['status']]??'status-pending'; ?>"><?php echo ucfirst($o['status']); ?></span></td>
              <td>
                <a href="order-details.php?id=<?php echo $o['id']; ?>" class="btn btn-outline btn-sm">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($totalPages>1): ?>
      <div class="pagination" style="padding:16px 20px;border-top:1px solid #E9ECEF;">
        <?php for($i=1;$i<=$totalPages;$i++): ?>
          <a href="?status=<?php echo $statusFilter; ?>&page=<?php echo $i; ?><?php echo $searchQuery?'&search='.urlencode($searchQuery):''; ?>" class="page-btn <?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
