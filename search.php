<?php
// BUG FIX: sanitize() is defined in functions.php which is loaded by header.php.
// The call MUST come after require_once so the function exists.
$pageTitle = 'Search';
require_once 'includes/header.php';

$searchQuery = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$pageTitle   = $searchQuery ? 'Search: '.htmlspecialchars($searchQuery) : 'Search';

if (empty($searchQuery)) redirect('shop.php');

$filters      = ['search' => $searchQuery];
$page         = max(1,(int)($_GET['page']??1));
$perPage      = 12;
$offset       = ($page-1)*$perPage;
$products     = getAllProducts($filters, $perPage, $offset);
$totalResults = countProducts($filters);
$totalPages   = ceil($totalResults/$perPage);

function renderStars($r){
    $o='';
    for($i=1;$i<=5;$i++)
        $o.='<svg class="'.($i<=$r?'star-on':'star-off').'" viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
    return $o;
}
?>

<!-- Page Hero -->
<div class="page-hero">
  <div class="container" style="position:relative;z-index:2;">
    <nav class="breadcrumb" style="color:rgba(255,255,255,0.5);">
      <a href="<?php echo SITE_URL; ?>" style="color:rgba(255,255,255,0.5);">Home</a>
      <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
      <span style="color:rgba(255,255,255,0.8);">Search</span>
    </nav>
    <h1 class="page-hero-title">Search Results</h1>
    <p class="page-hero-sub">
      <?php echo number_format($totalResults); ?> result<?php echo $totalResults!=1?'s':''; ?> for
      &ldquo;<strong style="color:#FEF3C7;"><?php echo htmlspecialchars($searchQuery); ?></strong>&rdquo;
    </p>
  </div>
</div>

<!-- Main Content -->
<div class="container" style="padding-top:40px;padding-bottom:72px;">

  <!-- Search bar -->
  <form action="search.php" method="GET"
        style="max-width:600px;display:flex;gap:8px;margin-bottom:36px;">
    <input type="text" name="q" class="form-input"
           value="<?php echo htmlspecialchars($searchQuery); ?>"
           placeholder="Search products…"
           style="flex:1;">
    <button type="submit" class="btn btn-gold">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
           stroke-width="2" stroke="currentColor" width="16" height="16">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
      </svg>
      Search
    </button>
  </form>

  <?php if (empty($products)): ?>
    <!-- Empty State -->
    <div style="text-align:center;padding:72px 20px;">
      <div style="width:88px;height:88px;border-radius:50%;background:var(--cream-dark);
                  display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.5" stroke="var(--stone-mid)" width="40" height="40">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
      </div>
      <h3 style="font-family:'Cormorant',serif;font-size:26px;font-weight:700;
                 color:var(--black);margin-bottom:10px;">No results found</h3>
      <p style="font-size:14px;color:var(--stone-mid);margin-bottom:28px;max-width:380px;margin-left:auto;margin-right:auto;">
        We couldn&rsquo;t find any products matching
        &ldquo;<strong><?php echo htmlspecialchars($searchQuery); ?></strong>&rdquo;.
        Try a different keyword or browse our full collection.
      </p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="shop.php" class="btn btn-gold">Browse All Products</a>
        <a href="index.php" class="btn btn-outline">Go Home</a>
      </div>
    </div>

  <?php else: ?>
    <!-- Results count + sort row -->
    <div style="display:flex;align-items:center;justify-content:space-between;
                gap:12px;margin-bottom:24px;flex-wrap:wrap;">
      <p style="font-size:13px;color:var(--stone-mid);">
        Showing <strong style="color:var(--black);"><?php echo number_format($totalResults); ?></strong>
        result<?php echo $totalResults!=1?'s':''; ?> for
        &ldquo;<strong style="color:var(--black);"><?php echo htmlspecialchars($searchQuery); ?></strong>&rdquo;
      </p>
    </div>

    <!-- Product Grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;"
         id="search-product-grid">
      <?php foreach ($products as $p): ?>
        <div class="product-card"
             onclick="window.location='product.php?id=<?php echo $p['id']; ?>'">
          <div class="product-card-img">
            <img src="<?php echo htmlspecialchars($p['image']); ?>"
                 alt="<?php echo htmlspecialchars($p['name']); ?>"
                 loading="lazy"
                 onerror="this.src='https://placehold.co/400x400/F5F5F4/78716C?text=Jewelry'">
            <?php if ($p['compare_price'] > $p['price']): ?>
              <span class="product-card-badge badge-sale">Sale</span>
            <?php endif; ?>
            <div class="product-card-actions">
              <button onclick="event.stopPropagation();addToCart(<?php echo $p['id']; ?>)"
                      class="icon-btn" title="Add to Cart" aria-label="Add to cart">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                     stroke-width="2" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
                </svg>
              </button>
            </div>
          </div>
          <div class="product-card-body">
            <div class="product-card-cat"><?php echo htmlspecialchars($p['category_name']); ?></div>
            <h3 class="product-card-name">
              <a href="product.php?id=<?php echo $p['id']; ?>"
                 onclick="event.stopPropagation()">
                <?php echo htmlspecialchars($p['name']); ?>
              </a>
            </h3>
            <div class="stars" style="margin-bottom:8px;">
              <?php echo renderStars((int)$p['rating']); ?>
              <span style="font-size:11px;color:var(--stone-mid);margin-left:4px;">
                (<?php echo $p['review_count']; ?>)
              </span>
            </div>
            <div class="product-card-price">
              <span class="price-current"><?php echo formatPrice($p['price']); ?></span>
              <?php if ($p['compare_price'] > $p['price']): ?>
                <span class="price-original"><?php echo formatPrice($p['compare_price']); ?></span>
              <?php endif; ?>
            </div>
            <?php if ($p['stock_quantity'] <= 5 && $p['stock_quantity'] > 0): ?>
              <div class="stock-low">Only <?php echo $p['stock_quantity']; ?> left!</div>
            <?php elseif ($p['stock_quantity'] == 0): ?>
              <div class="stock-out">Out of Stock</div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <!-- Prev -->
        <?php if ($page > 1): ?>
          <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $page-1; ?>"
             class="page-btn" aria-label="Previous page">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="2.5" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
          </a>
        <?php else: ?>
          <span class="page-btn disabled" aria-disabled="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="2.5" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
          </span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);
        if ($start > 1) {
            echo '<a href="?q='.urlencode($searchQuery).'&page=1" class="page-btn">1</a>';
            if ($start > 2) echo '<span class="page-btn" style="cursor:default;border:none;">…</span>';
        }
        for ($i = $start; $i <= $end; $i++) {
            echo '<a href="?q='.urlencode($searchQuery).'&page='.$i.'" class="page-btn'.($i==$page?' active':'').'">'.$i.'</a>';
        }
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) echo '<span class="page-btn" style="cursor:default;border:none;">…</span>';
            echo '<a href="?q='.urlencode($searchQuery).'&page='.$totalPages.'" class="page-btn">'.$totalPages.'</a>';
        }
        ?>

        <!-- Next -->
        <?php if ($page < $totalPages): ?>
          <a href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $page+1; ?>"
             class="page-btn" aria-label="Next page">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="2.5" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
          </a>
        <?php else: ?>
          <span class="page-btn disabled" aria-disabled="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="2.5" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<style>
@media (max-width: 768px) {
  #search-product-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 12px !important; }
}
@media (max-width: 480px) {
  #search-product-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
