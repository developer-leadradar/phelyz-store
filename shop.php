<?php
$pageTitle = "Shop";
require_once 'includes/header.php';

// ── Filters from GET ────────────────────────────────────
$filters = [];
if (!empty($_GET['category']))    $filters['category_id']  = (int)$_GET['category'];
if (!empty($_GET['search']))      $filters['search']        = sanitize($_GET['search']);
if (!empty($_GET['min_price']))   $filters['min_price']     = (float)$_GET['min_price'];
if (!empty($_GET['max_price']))   $filters['max_price']     = (float)$_GET['max_price'];
if (!empty($_GET['material']))    $filters['material']      = sanitize($_GET['material']);
if (!empty($_GET['metal_purity']))$filters['metal_purity']  = sanitize($_GET['metal_purity']);
if (!empty($_GET['stone_type']))  $filters['stone_type']    = sanitize($_GET['stone_type']);
if (!empty($_GET['brand']))       $filters['brand']         = sanitize($_GET['brand']);
if (!empty($_GET['gender']))      $filters['gender']        = sanitize($_GET['gender']);
if (!empty($_GET['style']))       $filters['style']         = sanitize($_GET['style']);
if (!empty($_GET['occasion']))    $filters['occasion']      = sanitize($_GET['occasion']);
if (isset($_GET['in_stock']))     $filters['in_stock']      = true;
if (isset($_GET['featured']))     $filters['featured']      = true;
if (!empty($_GET['rating']))      $filters['min_rating']    = (float)$_GET['rating'];
if (!empty($_GET['sort']))        $filters['sort']          = sanitize($_GET['sort']);

// Pagination
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

$products      = getAllProducts($filters, $perPage, $offset);
$totalProducts = countProducts($filters);
$totalPages    = ceil($totalProducts / $perPage);

// Filter option lists
$materials    = getFilterOptions('material');
$metalPurities= getFilterOptions('metal_purity');
$stoneTypes   = getFilterOptions('stone_type');
$brands       = getFilterOptions('brand');
$genders      = [['gender'=>'Men'],['gender'=>'Women'],['gender'=>'Unisex']];
$styles       = getFilterOptions('style');
$occasions    = getFilterOptions('occasion');

// Helpers
function removeFilter($key) {
    $p = $_GET; unset($p[$key],$p['page']);
    return 'shop.php' . ($p ? '?'.http_build_query($p) : '');
}
function activeFilter($key, $val=null) {
    if ($val===null) return isset($_GET[$key]) && $_GET[$key]!=='';
    return isset($_GET[$key]) && $_GET[$key]==$val;
}
function renderStars($r) {
    $o=''; for($i=1;$i<=5;$i++) $o.='<svg class="'.($i<=$r?'star-on':'star-off').'" viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
    return $o;
}
?>

<!-- Page Hero -->
<div class="page-hero">
  <div class="container" style="position:relative;z-index:2;">
    <nav class="breadcrumb" style="color:rgba(255,255,255,0.5);">
      <a href="<?php echo SITE_URL; ?>" style="color:rgba(255,255,255,0.5);">Home</a>
      <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
      <span style="color:rgba(255,255,255,0.8);">Shop</span>
    </nav>
    <h1 class="page-hero-title">Shop Our Collection</h1>
    <p class="page-hero-sub"><?php echo number_format($totalProducts); ?> piece<?php echo $totalProducts!=1?'s':''; ?> of fine jewellery</p>
  </div>
</div>

<!-- Active Filter Chips -->
<?php
$activeFilters = [];
$labelMap = ['category_id'=>'Category','min_price'=>'Min Price','max_price'=>'Max Price','material'=>'Material','metal_purity'=>'Purity','stone_type'=>'Stone','brand'=>'Brand','gender'=>'Gender','style'=>'Style','occasion'=>'Occasion','in_stock'=>'In Stock','featured'=>'Featured','min_rating'=>'Rating'];
foreach ($filters as $k=>$v) { if ($k!=='sort' && $k!=='category_id') $activeFilters[$k]=$v; if ($k==='category_id') $activeFilters['category']=$v; }
if (!empty($activeFilters)):
?>
<div style="background:var(--cream-dark);border-bottom:1px solid var(--cream-dark);padding:12px 0;">
  <div class="container" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
    <span style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--stone-mid);">Active:</span>
    <?php foreach ($activeFilters as $k=>$v): ?>
      <a href="<?php echo removeFilter($k); ?>"
         style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;background:var(--gold-pale);border:1px solid rgba(202,138,4,0.3);border-radius:99px;font-size:12px;font-weight:600;color:var(--black);transition:all 0.15s;"
         onmouseover="this.style.background='var(--gold)';this.style.color='white'"
         onmouseout="this.style.background='var(--gold-pale)';this.style.color='var(--black)'">
        <?php
          $label = $labelMap[$k] ?? ucfirst($k);
          if ($k === 'category') {
            $cat = getCategoryById((int)$v);
            $display = $cat ? $cat['name'] : $v;
          } else {
            $display = is_bool($v) ? 'Yes' : (string)$v;
          }
          echo htmlspecialchars($label) . ': ' . htmlspecialchars($display);
        ?>
        <svg viewBox="0 0 20 20" fill="currentColor" width="11" height="11"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
      </a>
    <?php endforeach; ?>
    <a href="shop.php" style="font-size:12px;font-weight:600;color:#EF4444;text-decoration:underline;margin-left:4px;">Clear all</a>
  </div>
</div>
<?php endif; ?>

<!-- Mobile Filter Button -->
<div style="display:none;position:sticky;bottom:0;z-index:40;padding:12px 16px;background:white;border-top:1px solid var(--cream-dark);box-shadow:0 -4px 20px rgba(28,25,23,0.10);" id="mobile-filter-bar">
  <button onclick="openFilterSheet()" class="btn btn-dark btn-full" style="justify-content:center;gap:8px;">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/></svg>
    Filters<?php if(!empty($activeFilters)):?> (<?php echo count($activeFilters);?>)<?php endif;?>
  </button>
</div>

<!-- Mobile Filter Sheet Backdrop -->
<div id="filter-backdrop" onclick="closeFilterSheet()" style="display:none;position:fixed;inset:0;background:rgba(28,25,23,0.5);z-index:200;"></div>

<!-- Mobile Filter Sheet -->
<div id="filter-sheet" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:201;background:white;border-radius:20px 20px 0 0;max-height:85vh;overflow-y:auto;transform:translateY(100%);transition:transform 0.35s cubic-bezier(0.4,0,0.2,1);">
  <div style="padding:16px 20px;border-bottom:1px solid var(--cream-dark);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:white;z-index:1;">
    <span style="font-weight:700;font-size:16px;">Filters</span>
    <button onclick="closeFilterSheet()" style="background:none;border:none;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--stone);">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>
  <div id="filter-sheet-content" style="padding:0 20px 100px;"></div>
  <div style="position:sticky;bottom:0;padding:16px 20px;background:white;border-top:1px solid var(--cream-dark);">
    <button onclick="document.getElementById('mobile-filter-form').submit()" class="btn btn-gold btn-full">Apply Filters</button>
  </div>
</div>

<!-- Main Layout -->
<div class="container" style="padding-top:32px;padding-bottom:64px;">
  <div style="display:flex;gap:28px;align-items:flex-start;">

    <!-- ── Sidebar Filter ─────────────────────── -->
    <aside style="width:240px;flex-shrink:0;display:block;" id="desktop-sidebar">
      <form method="GET" action="shop.php" id="filter-form">
        <?php if (!empty($filters['sort'])): ?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($filters['sort']); ?>"><?php endif; ?>

        <div class="card" style="overflow:hidden;padding:0;">
          <!-- Header -->
          <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--cream-dark);background:var(--cream);">
            <span style="font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--stone);">Filters</span>
            <?php if (!empty($activeFilters)): ?>
              <a href="shop.php<?php echo !empty($filters['sort'])?'?sort='.htmlspecialchars($filters['sort']):''; ?>" style="font-size:11px;color:#EF4444;font-weight:600;">Clear all</a>
            <?php endif; ?>
          </div>

          <?php
          // Helper to render a filter section
          function filterSection($title, $content) {
            return '<div style="border-bottom:1px solid var(--cream-dark);">
              <button type="button" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display===\'none\'?\'block\':\'none\';this.querySelector(\'svg\').style.transform=this.nextElementSibling.style.display===\'none\'?\'\':\' rotate(180deg)\'"
                style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:12px 18px;background:none;border:none;font-size:13px;font-weight:600;color:var(--black);cursor:pointer;">
                '.$title.'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="15" height="15" style="transition:transform 0.2s;"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
              </button>
              <div style="padding:4px 18px 14px;">'.$content.'</div>
            </div>';
          }
          function radioOpts($name, $items, $valKey, $current) {
            $out = '';
            foreach ($items as $item) {
              $v = htmlspecialchars($item[$valKey]);
              $checked = isset($current) && $current == $item[$valKey] ? 'checked' : '';
              $out .= '<label style="display:flex;align-items:center;gap:8px;padding:5px 0;cursor:pointer;">
                <input type="radio" name="'.$name.'" value="'.$v.'" '.$checked.' style="accent-color:var(--gold);width:14px;height:14px;">
                <span style="font-size:13px;color:var(--stone);">'.$v.'</span>
              </label>';
            }
            return $out;
          }

          // Category
          if (!empty($categories)) {
            $opts = '';
            foreach ($categories as $cat) {
              $checked = (isset($filters['category_id']) && $filters['category_id']==$cat['id']) ? 'checked' : '';
              $opts .= '<label style="display:flex;align-items:center;gap:8px;padding:5px 0;cursor:pointer;">
                <input type="radio" name="category" value="'.(int)$cat['id'].'" '.$checked.' style="accent-color:var(--gold);width:14px;height:14px;">
                <span style="font-size:13px;color:var(--stone);">'.htmlspecialchars($cat['name']).'</span>
              </label>';
            }
            echo filterSection('Category', $opts);
          }

          // Price Range
          $priceContent = '<div style="display:flex;gap:8px;align-items:center;">
            <input type="number" name="min_price" placeholder="Min ₦" value="'.($filters['min_price']??'').'" style="width:100%;padding:8px 10px;border:1.5px solid var(--cream-dark);border-radius:6px;font-size:13px;font-family:inherit;outline:none;" onfocus="this.style.borderColor=\'var(--gold)\'" onblur="this.style.borderColor=\'var(--cream-dark)\'">
            <span style="color:var(--stone-mid);">–</span>
            <input type="number" name="max_price" placeholder="Max ₦" value="'.($filters['max_price']??'').'" style="width:100%;padding:8px 10px;border:1.5px solid var(--cream-dark);border-radius:6px;font-size:13px;font-family:inherit;outline:none;" onfocus="this.style.borderColor=\'var(--gold)\'" onblur="this.style.borderColor=\'var(--cream-dark)\'">
          </div>';
          echo filterSection('Price Range', $priceContent);

          if (!empty($materials))    echo filterSection('Material',    radioOpts('material',    $materials,    'material',    $filters['material']??null));
          if (!empty($metalPurities))echo filterSection('Metal Purity', radioOpts('metal_purity',$metalPurities,'metal_purity',$filters['metal_purity']??null));
          if (!empty($stoneTypes))   echo filterSection('Stone Type',   radioOpts('stone_type',  $stoneTypes,   'stone_type',  $filters['stone_type']??null));
          if (!empty($brands))       echo filterSection('Brand',        radioOpts('brand',       $brands,       'brand',       $filters['brand']??null));

          // Gender
          $genderContent = radioOpts('gender', $genders, 'gender', $filters['gender']??null);
          echo filterSection('Gender', $genderContent);

          // In Stock toggle
          $inStockContent = '<label style="display:flex;align-items:center;gap:8px;padding:5px 0;cursor:pointer;">
            <input type="checkbox" name="in_stock" value="1" '.( isset($filters['in_stock'])&&$filters['in_stock']?'checked':'').' style="accent-color:var(--gold);width:14px;height:14px;">
            <span style="font-size:13px;color:var(--stone);">In Stock Only</span>
          </label>';
          echo filterSection('Availability', $inStockContent);
          ?>

          <!-- Apply button -->
          <div style="padding:14px 18px;">
            <button type="submit" class="btn btn-gold btn-full btn-sm">Apply Filters</button>
          </div>
        </div>
      </form>
    </aside>

    <!-- ── Products Area ──────────────────────── -->
    <div style="flex:1;min-width:0;">

      <!-- Sort bar -->
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <p style="font-size:13px;color:var(--stone-mid);">
          Showing <strong style="color:var(--black);"><?php echo number_format($totalProducts); ?></strong> result<?php echo $totalProducts!=1?'s':''; ?>
          <?php if (!empty($filters['search'])): ?> for "<strong><?php echo htmlspecialchars($filters['search']); ?></strong>"<?php endif; ?>
        </p>
        <form method="GET" action="shop.php" id="sort-form" style="display:flex;align-items:center;gap:8px;">
          <?php foreach ($_GET as $k=>$v): if ($k==='sort') continue; ?>
            <input type="hidden" name="<?php echo htmlspecialchars($k); ?>" value="<?php echo htmlspecialchars((string)$v); ?>">
          <?php endforeach; ?>
          <label style="font-size:12px;font-weight:600;color:var(--stone-mid);white-space:nowrap;">Sort by:</label>
          <select name="sort" onchange="this.form.submit()" class="form-input form-select" style="padding:8px 36px 8px 12px;font-size:13px;width:auto;">
            <option value="">Default</option>
            <option value="newest" <?php echo ($filters['sort']??'')==='newest'?'selected':''; ?>>Newest First</option>
            <option value="price_asc" <?php echo ($filters['sort']??'')==='price_asc'?'selected':''; ?>>Price: Low to High</option>
            <option value="price_desc" <?php echo ($filters['sort']??'')==='price_desc'?'selected':''; ?>>Price: High to Low</option>
            <option value="rating" <?php echo ($filters['sort']??'')==='rating'?'selected':''; ?>>Highest Rated</option>
          </select>
        </form>
      </div>

      <!-- Product Grid -->
      <?php if (!empty($products)): ?>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;" id="product-grid">
          <?php foreach ($products as $p): ?>
            <div class="product-card" onclick="window.location='product.php?id=<?php echo $p['id']; ?>'">
              <div class="product-card-img">
                <img src="<?php echo htmlspecialchars($p['image']); ?>"
                     alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy"
                     onerror="this.src='https://placehold.co/400x400/F5F5F4/78716C?text=Jewelry'">
                <?php if ($p['compare_price'] > $p['price']): ?>
                  <span class="product-card-badge badge-sale">Sale</span>
                <?php elseif ($p['is_featured']): ?>
                  <span class="product-card-badge badge-featured">Featured</span>
                <?php endif; ?>
                <div class="product-card-actions">
                  <button onclick="event.stopPropagation();addToCart(<?php echo $p['id']; ?>)" class="icon-btn" title="Add to Cart">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                  </button>
                  <?php if (isLoggedIn()): ?>
                  <button onclick="event.stopPropagation();addToWishlist(<?php echo $p['id']; ?>)" class="icon-btn" title="Wishlist">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                  </button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="product-card-body">
                <div class="product-card-cat"><?php echo htmlspecialchars($p['category_name']); ?></div>
                <h3 class="product-card-name"><a href="product.php?id=<?php echo $p['id']; ?>" onclick="event.stopPropagation()"><?php echo htmlspecialchars($p['name']); ?></a></h3>
                <?php if ($p['material']): ?><div class="product-card-meta"><?php echo htmlspecialchars($p['metal_purity'].' '.$p['material']); ?></div><?php endif; ?>
                <div class="stars" style="margin-bottom:8px;"><?php echo renderStars((int)$p['rating']); ?><span style="font-size:11px;color:var(--stone-mid);margin-left:4px;">(<?php echo $p['review_count']; ?>)</span></div>
                <div class="product-card-price">
                  <span class="price-current"><?php echo formatPrice($p['price']); ?></span>
                  <?php if ($p['compare_price']>$p['price']): ?><span class="price-original"><?php echo formatPrice($p['compare_price']); ?></span><?php endif; ?>
                </div>
                <?php if ($p['stock_quantity']<=5&&$p['stock_quantity']>0): ?><div class="stock-low">Only <?php echo $p['stock_quantity']; ?> left!</div>
                <?php elseif($p['stock_quantity']==0): ?><div class="stock-out">Out of Stock</div><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page-1])); ?>" class="page-btn">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
          <?php else: ?><span class="page-btn disabled"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg></span><?php endif; ?>
          <?php
          $start=max(1,$page-2); $end=min($totalPages,$page+2);
          if($start>1){echo '<a href="?'.http_build_query(array_merge($_GET,['page'=>1])).'" class="page-btn">1</a>'; if($start>2) echo '<span class="page-btn" style="cursor:default;border:none;">…</span>';}
          for($i=$start;$i<=$end;$i++) echo '<a href="?'.http_build_query(array_merge($_GET,['page'=>$i])).'" class="page-btn'.($i==$page?' active':'').'">'.$i.'</a>';
          if($end<$totalPages){if($end<$totalPages-1) echo '<span class="page-btn" style="cursor:default;border:none;">…</span>'; echo '<a href="?'.http_build_query(array_merge($_GET,['page'=>$totalPages])).'" class="page-btn">'.$totalPages.'</a>';}
          ?>
          <?php if ($page < $totalPages): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page+1])); ?>" class="page-btn">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
          <?php else: ?><span class="page-btn disabled"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg></span><?php endif; ?>
        </div>
        <?php endif; ?>

      <?php else: ?>
        <!-- Empty state -->
        <div style="text-align:center;padding:80px 20px;">
          <div style="width:80px;height:80px;border-radius:50%;background:var(--cream-dark);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--stone-mid)" width="36" height="36"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
          </div>
          <h3 style="font-family:'Cormorant',serif;font-size:24px;font-weight:700;color:var(--black);margin-bottom:8px;">No products found</h3>
          <p style="font-size:14px;color:var(--stone-mid);margin-bottom:24px;">Try adjusting your filters or search term.</p>
          <a href="shop.php" class="btn btn-gold">Browse All Products</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
/* Mobile: hide desktop sidebar, show filter bar */
@media(max-width:1024px) {
  #desktop-sidebar { display:none !important; }
  #product-grid { grid-template-columns:repeat(2,1fr) !important; }
  #mobile-filter-bar { display:block !important; }
}
@media(max-width:480px) {
  #product-grid { grid-template-columns:1fr !important; }
}
</style>

<script>
function openFilterSheet(){
  document.getElementById('filter-backdrop').style.display='block';
  var s=document.getElementById('filter-sheet');
  s.style.display='block';
  setTimeout(function(){s.style.transform='translateY(0)';},10);
  document.body.style.overflow='hidden';
  // Clone filter form into sheet
  if(!document.getElementById('mobile-filter-form')){
    var original=document.getElementById('filter-form');
    var clone=original.cloneNode(true);
    clone.id='mobile-filter-form';
    document.getElementById('filter-sheet-content').appendChild(clone);
  }
}
function closeFilterSheet(){
  var s=document.getElementById('filter-sheet');
  s.style.transform='translateY(100%)';
  setTimeout(function(){s.style.display='none';document.getElementById('filter-backdrop').style.display='none';},350);
  document.body.style.overflow='';
}
</script>

<?php require_once 'includes/footer.php'; ?>
