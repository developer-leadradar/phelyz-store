<?php
$pageTitle = "Shop";
require_once 'includes/header.php';
require_once 'includes/product-card.php';

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
    <p class="page-hero-sub"><?php echo number_format($totalProducts); ?> piece<?php echo $totalProducts!=1?'s':''; ?> of fine jewelry</p>
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
    <aside style="width:240px;flex-shrink:0;" id="desktop-sidebar">
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
            <span style="color:var(--stone-mid);">-</span>
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

          <!-- Apply button (desktop only - hidden when cloned into mobile sheet) -->
          <div class="desktop-apply-wrap" style="padding:14px 18px;">
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
          <?php foreach ($products as $p) renderProductCard($p); ?>
        </div>

        <!-- Infinite scroll sentinel + end-of-results notice -->
        <div id="scroll-sentinel" style="height:1px;"></div>

        <div id="scroll-loader" style="display:none;text-align:center;padding:34px 20px;">
          <div class="scroll-spinner" style="width:26px;height:26px;border:2.5px solid var(--cream-dark);border-top-color:var(--gold);border-radius:50%;margin:0 auto 10px;animation:spin 0.8s linear infinite;"></div>
          <span style="font-size:12.5px;color:var(--stone-mid);">Loading more pieces…</span>
        </div>

        <div id="scroll-end" style="display:none;text-align:center;padding:38px 20px 10px;">
          <div style="display:inline-flex;align-items:center;gap:10px;color:var(--stone-mid);font-size:13px;">
            <span style="height:1px;width:36px;background:var(--cream-dark);"></span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="var(--gold)" width="14" height="14"><path d="M11.48 3.5a.56.56 0 011.04 0l2.12 5.11c.09.2.28.34.48.35l5.52.44c.5.04.7.66.32.99l-4.2 3.6a.56.56 0 00-.19.56l1.29 5.38a.56.56 0 01-.84.61l-4.73-2.88a.56.56 0 00-.58 0l-4.73 2.88a.56.56 0 01-.84-.61l1.29-5.38a.56.56 0 00-.19-.56l-4.2-3.6a.56.56 0 01.32-.99l5.52-.44c.2-.01.39-.15.48-.35L11.48 3.5z"/></svg>
            <span id="scroll-end-text">You've reached the end</span>
            <span style="height:1px;width:36px;background:var(--cream-dark);"></span>
          </div>
        </div>

        <noscript>
          <div style="text-align:center;padding:24px;font-size:13px;color:var(--stone-mid);">
            Enable JavaScript to browse the full collection.
          </div>
        </noscript>

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

<script>
function openFilterSheet(){
  document.getElementById('filter-backdrop').style.display='block';
  var s=document.getElementById('filter-sheet');
  s.style.display='block';
  setTimeout(function(){s.style.transform='translateY(0)';},10);
  document.body.style.overflow='hidden';
  // Clone filter form into sheet (once only)
  if(!document.getElementById('mobile-filter-form')){
    var original=document.getElementById('filter-form');
    var clone=original.cloneNode(true);
    clone.id='mobile-filter-form';
    // Remove the desktop Apply button from the clone - the sheet has its own sticky button
    var innerApply=clone.querySelector('.desktop-apply-wrap');
    if(innerApply) innerApply.remove();
    document.getElementById('filter-sheet-content').appendChild(clone);
  }
}
function closeFilterSheet(){
  var s=document.getElementById('filter-sheet');
  s.style.transform='translateY(100%)';
  setTimeout(function(){s.style.display='none';document.getElementById('filter-backdrop').style.display='none';},350);
  document.body.style.overflow='';
}

/* ── Infinite scroll ─────────────────────────────────────────
   Loads the next 12 cards as the shopper nears the bottom, keeping whatever
   category/filters are active (they're already in the query string). */
(function () {
  var grid     = document.getElementById('product-grid');
  var sentinel = document.getElementById('scroll-sentinel');
  var loader   = document.getElementById('scroll-loader');
  var endBox   = document.getElementById('scroll-end');
  if (!grid || !sentinel) return;

  var page    = <?php echo (int)$page; ?>;
  var total   = <?php echo (int)$totalProducts; ?>;
  var hasMore = grid.children.length < total;
  var loading = false;

  if (!hasMore) { showEnd(); return; }

  function showEnd() {
    if (!endBox) return;
    var t = document.getElementById('scroll-end-text');
    if (t) {
      t.textContent = total === 0
        ? 'No pieces found'
        : "That's all " + total + (total === 1 ? ' piece' : ' pieces');
    }
    endBox.style.display = 'block';
  }

  // Preserve the current filters, swap in the next page number
  function nextUrl() {
    var params = new URLSearchParams(window.location.search);
    params.set('page', page + 1);
    return '<?php echo SITE_URL; ?>/api/load-products.php?' + params.toString();
  }

  function loadMore() {
    if (loading || !hasMore) return;
    loading = true;
    if (loader) loader.style.display = 'block';

    fetch(nextUrl())
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || !d.ok) throw new Error('bad response');
        if (d.html && d.html.trim()) {
          var tmp = document.createElement('div');
          tmp.innerHTML = d.html;
          while (tmp.firstElementChild) grid.appendChild(tmp.firstElementChild);
        }
        page    = d.page;
        total   = d.total;
        hasMore = !!d.has_more;
        if (loader) loader.style.display = 'none';
        loading = false;
        if (!hasMore) { showEnd(); if (io) io.disconnect(); }
      })
      .catch(function () {
        if (loader) loader.style.display = 'none';
        loading = false;
        hasMore = false;
        showEnd();
      });
  }

  var io = null;
  if ('IntersectionObserver' in window) {
    io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) loadMore(); });
    }, { rootMargin: '600px 0px' });   // start fetching before it's visible
    io.observe(sentinel);
  } else {
    // Older browsers: fall back to a throttled scroll check
    window.addEventListener('scroll', function () {
      if (loading || !hasMore) return;
      if (sentinel.getBoundingClientRect().top - window.innerHeight < 600) loadMore();
    }, { passive: true });
  }
})();
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<?php require_once 'includes/footer.php'; ?>
