<?php
$pageTitle = "Customers";
require_once 'includes/header.php';

$db          = getDB();
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sql         = "SELECT * FROM users WHERE role = 'customer'";
$params      = [];
if ($searchQuery) { $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)"; $t='%'.$searchQuery.'%'; $params=[$t,$t,$t]; }
$page    = max(1,(int)($_GET['page']??1));
$perPage = 20;
$offset  = ($page-1)*$perPage;
$countSql= str_replace("SELECT *","SELECT COUNT(*) as total",$sql);
$total   = $db->fetchOne($countSql,$params)['total']??0;
$totalPages=ceil($total/$perPage);
$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[]=$perPage;$params[]=$offset;
$customers=$db->fetchAll($sql,$params);
?>

<!-- Search bar -->
<form method="GET" style="display:flex;gap:8px;margin-bottom:20px;max-width:400px;">
  <input type="text" name="search" class="form-input" placeholder="Search customers…" value="<?php echo htmlspecialchars($searchQuery); ?>" style="flex:1;padding:9px 14px;font-size:13px;">
  <button type="submit" class="btn btn-dark btn-sm">Search</button>
  <?php if ($searchQuery): ?><a href="customers.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
</form>

<div style="background:white;border:1px solid #E9ECEF;border-radius:14px;overflow:hidden;">
  <div style="padding:14px 20px;border-bottom:1px solid #E9ECEF;"><span style="font-size:13px;font-weight:600;color:var(--stone-mid);"><?php echo number_format($total); ?> customer<?php echo $total!=1?'s':''; ?></span></div>
  <?php if (empty($customers)): ?>
    <div style="text-align:center;padding:48px;color:var(--stone-mid);">No customers found.</div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px;">
                  <div style="width:34px;height:34px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-family:'Cormorant',serif;font-size:15px;font-weight:700;color:white;flex-shrink:0;"><?php echo strtoupper(substr($c['first_name'],0,1)); ?></div>
                  <span style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars($c['first_name'].' '.$c['last_name']); ?></span>
                </div>
              </td>
              <td style="font-size:13px;color:var(--stone-mid);"><?php echo htmlspecialchars($c['email']); ?></td>
              <td style="font-size:13px;color:var(--stone-mid);"><?php echo htmlspecialchars($c['phone']??'—'); ?></td>
              <td style="font-size:12px;color:var(--stone-mid);"><?php echo formatDate($c['created_at']); ?></td>
              <td><span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:99px;<?php echo $c['is_active']?'background:#F0FDF4;color:#166534;':'background:#FEF2F2;color:#991B1B;'; ?>"><?php echo $c['is_active']?'Active':'Inactive'; ?></span></td>
              <td><a href="customer-details.php?id=<?php echo $c['id']; ?>" class="btn btn-outline btn-sm">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($totalPages>1): ?>
      <div class="pagination" style="padding:16px 20px;border-top:1px solid #E9ECEF;">
        <?php for($i=1;$i<=$totalPages;$i++): ?><a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$i])); ?>" class="page-btn <?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a><?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
