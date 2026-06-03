<?php
$pageTitle = "Products";
require_once 'includes/header.php';

$db             = getDB();
$searchQuery    = isset($_GET['search'])   ? sanitize($_GET['search'])   : '';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category']      : 0;
$statusFilter   = isset($_GET['filter'])   ? sanitize($_GET['filter'])   : 'all';

$sql    = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];
if ($searchQuery)    { $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)"; $params[]='%'.$searchQuery.'%'; $params[]='%'.$searchQuery.'%'; }
if ($categoryFilter) { $sql .= " AND p.category_id = ?"; $params[] = $categoryFilter; }
if ($statusFilter==='active')      { $sql .= " AND p.is_active = 1"; }
elseif ($statusFilter==='featured'){ $sql .= " AND p.is_featured = 1"; }
elseif ($statusFilter==='low_stock'){ $sql .= " AND p.stock_quantity > 0 AND p.stock_quantity < 5"; }
elseif ($statusFilter==='out_of_stock'){ $sql .= " AND p.stock_quantity = 0"; }

$page    = max(1,(int)($_GET['page']??1));
$perPage = 20;
$offset  = ($page-1)*$perPage;
$countSql= str_replace("p.*, c.name as category_name","COUNT(*) as total",$sql);
$total   = $db->fetchOne($countSql,$params)['total']??0;
$totalPages=ceil($total/$perPage);
$sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[]=$perPage; $params[]=$offset;
$products   = $db->fetchAll($sql,$params);
$categories = getAllCategories();
?>

<!-- Top bar -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
  <div style="display:flex;gap:8px;flex-wrap:wrap;width:100%;min-width:0;">
    <form method="GET" style="display:flex;gap:6px;flex-wrap:wrap;width:100%;min-width:0;">
      <input type="text" name="search" class="form-input" placeholder="Search products…" value="<?php echo htmlspecialchars($searchQuery); ?>" style="width:200px;padding:8px 12px;font-size:13px;">
      <select name="category" class="form-input form-select" style="width:160px;padding:8px 12px;font-size:13px;">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo $categoryFilter==$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
      </select>
      <select name="filter" class="form-input form-select" style="width:150px;padding:8px 12px;font-size:13px;">
        <option value="all" <?php echo $statusFilter==='all'?'selected':''; ?>>All Products</option>
        <option value="active" <?php echo $statusFilter==='active'?'selected':''; ?>>Active</option>
        <option value="featured" <?php echo $statusFilter==='featured'?'selected':''; ?>>Featured</option>
        <option value="low_stock" <?php echo $statusFilter==='low_stock'?'selected':''; ?>>Low Stock</option>
        <option value="out_of_stock" <?php echo $statusFilter==='out_of_stock'?'selected':''; ?>>Out of Stock</option>
      </select>
      <button type="submit" class="btn btn-dark btn-sm">Filter</button>
    </form>
  </div>
  <a href="add-product.php" class="btn btn-gold btn-sm">+ Add Product</a>
</div>

<div style="background:white;border:1px solid #E9ECEF;border-radius:14px;overflow:hidden;">
  <div style="padding:14px 20px;border-bottom:1px solid #E9ECEF;">
    <span style="font-size:13px;font-weight:600;color:var(--stone-mid);"><?php echo number_format($total); ?> product<?php echo $total!=1?'s':''; ?></span>
  </div>
  <?php if (empty($products)): ?>
    <div style="text-align:center;padding:48px;color:var(--stone-mid);">No products found. <a href="add-product.php" style="color:var(--gold);">Add one →</a></div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th style="width:100px;min-width:100px;padding-left:16px;padding-right:8px;"></th><th>Product</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($products as $p): ?>
            <tr>
              <td style="min-width:100px;padding:8px 8px 8px 16px;">
                <div style="width:80px;height:44px;border-radius:8px;overflow:hidden;flex-shrink:0;">
                  <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block;" onerror="this.src='https://placehold.co/80x44/F5F5F4/78716C?text=J'">
                </div>
              </td>
              <td>
                <div style="font-weight:700;font-size:13px;color:var(--black);"><?php echo htmlspecialchars($p['name']); ?></div>
                <?php if ($p['is_featured']): ?><span style="font-size:10px;font-weight:700;background:rgba(202,138,4,0.10);color:var(--gold);padding:2px 6px;border-radius:4px;">Featured</span><?php endif; ?>
              </td>
              <td style="font-size:12px;color:var(--stone-mid);"><?php echo htmlspecialchars($p['sku']??'—'); ?></td>
              <td style="font-size:13px;"><?php echo htmlspecialchars($p['category_name']??'—'); ?></td>
              <td style="font-weight:700;"><?php echo formatPrice($p['price']); ?></td>
              <td>
                <?php if ($p['stock_quantity']===0||$p['stock_quantity']==='0'): ?>
                  <span style="color:#EF4444;font-weight:700;font-size:12px;">Out of Stock</span>
                <?php elseif ($p['stock_quantity']<5): ?>
                  <span style="color:#F59E0B;font-weight:700;font-size:12px;"><?php echo $p['stock_quantity']; ?> left</span>
                <?php else: ?>
                  <span style="color:#22C55E;font-weight:600;font-size:13px;"><?php echo $p['stock_quantity']; ?></span>
                <?php endif; ?>
              </td>
              <td><span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:99px;<?php echo $p['is_active']?'background:#F0FDF4;color:#166534;':'background:#F5F5F4;color:var(--stone-mid);'; ?>"><?php echo $p['is_active']?'Active':'Inactive'; ?></span></td>
              <td>
                <div style="display:flex;gap:6px;">
                  <a href="edit-product.php?id=<?php echo $p['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                  <a href="delete-product.php?id=<?php echo $p['id']; ?>" onclick="return confirmDelete('Delete this product?')" class="btn btn-sm" style="color:#EF4444;border:1.5px solid #FECACA;">Delete</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($totalPages>1): ?>
      <div class="pagination" style="padding:16px 20px;border-top:1px solid #E9ECEF;">
        <?php for($i=1;$i<=$totalPages;$i++): ?>
          <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$i])); ?>" class="page-btn <?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
