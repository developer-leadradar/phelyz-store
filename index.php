<?php
$pageTitle = "Home";
$pageDescription = "Discover exquisite diamonds and fine jewelry at Phelyz Store — certified authentic, free shipping over ₦50,000.";
require_once 'includes/header.php';

$featuredProducts = getFeaturedProducts(8);
$newArrivals      = getAllProducts(['sort' => 'newest'], 4);

// Helper: render star SVGs
function renderStars($rating) {
  $out = '';
  for ($i = 1; $i <= 5; $i++) {
    if ($i <= $rating) {
      $out .= '<svg class="star-on" viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
    } else {
      $out .= '<svg class="star-off" viewBox="0 0 20 20" fill="currentColor" width="13" height="13"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
    }
  }
  return $out;
}
?>

<!-- ═══════════════════════════════════════
     HERO
══════════════════════════════════════════ -->
<section style="background:linear-gradient(135deg,#1C1917 0%,#292524 60%,#1C1917 100%);min-height:90vh;display:flex;align-items:center;position:relative;overflow:hidden;">
  <!-- Radial glow -->
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 70% at 70% 50%,rgba(202,138,4,0.18),transparent);pointer-events:none;"></div>
  <!-- Subtle grid texture -->
  <div style="position:absolute;inset:0;background-image:url('data:image/svg+xml,%3Csvg width=\'40\' height=\'40\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M0 40L40 0M0 0l40 40\' stroke=\'rgba(202,138,4,0.04)\' stroke-width=\'1\'/%3E%3C/svg%3E');pointer-events:none;"></div>

  <div class="container" style="position:relative;z-index:2;padding-top:60px;padding-bottom:60px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center;" class="hero-grid" id="hero-main-grid">

      <!-- Text -->
      <div class="animate-in">
        <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(202,138,4,0.12);border:1px solid rgba(202,138,4,0.25);border-radius:99px;padding:6px 16px;margin-bottom:24px;">
          <svg width="12" height="12" viewBox="0 0 20 20" fill="#CA8A04"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
          <span style="font-size:12px;font-weight:600;letter-spacing:0.08em;color:#FEF3C7;text-transform:uppercase">Certified Fine Jewelry</span>
        </div>

        <h1 style="font-family:'Cormorant',serif;font-size:clamp(40px,5vw,68px);font-weight:700;color:white;line-height:1.05;letter-spacing:-0.03em;margin-bottom:20px;">
          Timeless<br>
          <em style="color:#CA8A04;font-style:italic">Elegance</em><br>
          Perfected
        </h1>

        <p style="font-size:16px;color:rgba(255,255,255,0.60);line-height:1.75;max-width:440px;margin-bottom:36px;">
          Exquisite diamonds and fine jewelry crafted to last a lifetime. Celebrate your most precious moments with pieces that tell your story.
        </p>

        <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:40px;">
          <a href="shop.php" class="btn btn-gold btn-lg">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/></svg>
            Shop Collection
          </a>
          <a href="shop.php?featured=1" class="btn btn-outline btn-lg" style="color:rgba(255,255,255,0.80);border-color:rgba(255,255,255,0.25);">
            View Featured
          </a>
        </div>

        <!-- Trust pills -->
        <div style="display:flex;flex-wrap:wrap;gap:16px;">
          <?php
          $trustItems = [
            ['Certified Authentic','M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['Free Shipping ₦50k+','M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H3m16.5 0h-1.5m-1.5 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 4.5h19.5M3.75 7.5h16.5'],
            ['30-Day Returns','M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3'],
          ];
          foreach ($trustItems as [$label, $path]): ?>
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:24px;height:24px;border-radius:50%;background:rgba(202,138,4,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="#CA8A04" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $path; ?>"/></svg>
              </div>
              <span style="font-size:13px;font-weight:500;color:rgba(255,255,255,0.65);"><?php echo $label; ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Hero Image -->
      <div style="position:relative;" class="animate-in" style="animation-delay:200ms">
        <!-- Main image card -->
        <div style="border-radius:24px;overflow:hidden;box-shadow:0 40px 80px rgba(0,0,0,0.5);position:relative;aspect-ratio:4/5;">
          <img src="https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=700&h=900&fit=crop&q=80"
               alt="Luxury diamond jewelry collection"
               style="width:100%;height:100%;object-fit:cover;">
          <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(28,25,23,0.3) 0%,transparent 50%);"></div>
        </div>
        <!-- Floating stat card -->
        <div style="position:absolute;bottom:28px;left:-24px;background:rgba(250,250,249,0.90);backdrop-filter:blur(16px);border:1px solid rgba(202,138,4,0.20);border-radius:16px;padding:16px 20px;box-shadow:0 8px 32px rgba(28,25,23,0.20);">
          <div style="font-family:'Cormorant',serif;font-size:26px;font-weight:700;color:var(--black);line-height:1;">500+</div>
          <div style="font-size:12px;font-weight:600;color:var(--stone-mid);letter-spacing:0.06em;text-transform:uppercase;margin-top:2px;">Unique Pieces</div>
        </div>
        <!-- Floating rating card -->
        <div style="position:absolute;top:28px;right:-20px;background:rgba(250,250,249,0.90);backdrop-filter:blur(16px);border:1px solid rgba(202,138,4,0.20);border-radius:16px;padding:14px 18px;box-shadow:0 8px 32px rgba(28,25,23,0.20);display:flex;align-items:center;gap:10px;">
          <div style="background:linear-gradient(135deg,#CA8A04,#D97706);border-radius:10px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg viewBox="0 0 20 20" fill="white" width="18" height="18"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
          </div>
          <div>
            <div style="font-size:14px;font-weight:700;color:var(--black);line-height:1.2;">4.9/5 Rating</div>
            <div style="font-size:11px;color:var(--stone-mid);">2,400+ Reviews</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
     CATEGORIES
══════════════════════════════════════════ -->
<section style="padding:72px 0;background:var(--white);">
  <div class="container">
    <div style="text-align:center;margin-bottom:40px;">
      <p class="section-eyebrow">Browse Our Collections</p>
      <h2 class="section-title">Shop by Category</h2>
      <div class="section-divider" style="margin:12px auto 0;"></div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;">
      <?php
      $catIcons = [
        'rings'      => 'M9 3.75a3 3 0 00-3 3v.75m3-3.75h6m-6 0V3m0 .75V6.75m6-3a3 3 0 013 3v.75m-3-3.75V3m0 .75V6.75M6.75 6.75v7.5a3 3 0 003 3h3a3 3 0 003-3v-7.5M3.75 6.75h16.5',
        'necklaces'  => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
        'bracelets'  => 'M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zm0 0c0 1.657 1.007 3 2.25 3S21 13.657 21 12a9 9 0 10-2.636 6.364M16.5 12V8.25',
        'earrings'   => 'M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z',
        'pendants'   => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
        'watches'    => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'default'    => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z',
      ];
      foreach ($categories as $cat):
        $slug = $cat['slug'] ?? '';
        $icon = $catIcons[$slug] ?? $catIcons['default'];
      ?>
        <a href="shop.php?category=<?php echo $cat['id']; ?>"
           style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:24px 12px;border-radius:16px;border:1.5px solid var(--cream-dark);background:var(--cream);text-align:center;transition:all 0.2s;cursor:pointer;"
           onmouseover="this.style.borderColor='var(--gold)';this.style.background='white';this.style.boxShadow='var(--shadow-md)';this.style.transform='translateY(-4px)'"
           onmouseout="this.style.borderColor='var(--cream-dark)';this.style.background='var(--cream)';this.style.boxShadow='none';this.style.transform='none'">
          <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,rgba(202,138,4,0.10),rgba(202,138,4,0.18));display:flex;align-items:center;justify-content:center;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#CA8A04" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $icon; ?>"/></svg>
          </div>
          <div>
            <div style="font-size:13px;font-weight:700;color:var(--black);margin-bottom:3px;"><?php echo htmlspecialchars($cat['name']); ?></div>
            <div style="font-size:11px;color:var(--gold);font-weight:600;letter-spacing:0.06em;text-transform:uppercase;">View →</div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
     FEATURED PRODUCTS
══════════════════════════════════════════ -->
<section style="padding:80px 0;background:var(--cream);">
  <div class="container">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:40px;flex-wrap:wrap;gap:16px;">
      <div>
        <p class="section-eyebrow">Handpicked for You</p>
        <h2 class="section-title">Featured Collection</h2>
        <div class="section-divider"></div>
      </div>
      <a href="shop.php?featured=1" class="btn btn-outline">View All Featured</a>
    </div>

    <?php if (!empty($featuredProducts)): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;">
      <?php foreach ($featuredProducts as $p): ?>
        <div class="product-card" onclick="window.location='product.php?id=<?php echo $p['id']; ?>'">
          <div class="product-card-img">
            <img src="<?php echo htmlspecialchars($p['image']); ?>"
                 alt="<?php echo htmlspecialchars($p['name']); ?>"
                 loading="lazy"
                 onerror="this.src='https://placehold.co/400x400/F5F5F4/78716C?text=Jewelry'">
            <?php if ($p['compare_price'] > $p['price']): ?>
              <span class="product-card-badge badge-sale">Sale</span>
            <?php elseif ($p['is_featured']): ?>
              <span class="product-card-badge badge-featured">Featured</span>
            <?php endif; ?>
            <div class="product-card-actions">
              <button onclick="event.stopPropagation();addToCart(<?php echo $p['id']; ?>)" class="icon-btn" title="Add to Cart">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
              </button>
              <?php if (isLoggedIn()): ?>
              <button onclick="event.stopPropagation();addToWishlist(<?php echo $p['id']; ?>)" class="icon-btn" title="Add to Wishlist">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
              </button>
              <?php endif; ?>
            </div>
          </div>
          <div class="product-card-body">
            <div class="product-card-cat"><?php echo htmlspecialchars($p['category_name']); ?></div>
            <h3 class="product-card-name">
              <a href="product.php?id=<?php echo $p['id']; ?>" onclick="event.stopPropagation()"><?php echo htmlspecialchars($p['name']); ?></a>
            </h3>
            <?php if ($p['material']): ?>
              <div class="product-card-meta"><?php echo htmlspecialchars($p['metal_purity'].' '.$p['material']); ?></div>
            <?php endif; ?>
            <div class="stars" style="margin-bottom:8px;">
              <?php echo renderStars((int)$p['rating']); ?>
              <span style="font-size:11px;color:var(--stone-mid);margin-left:4px;">(<?php echo $p['review_count']; ?>)</span>
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
    <?php endif; ?>
  </div>
</section>

<!-- ═══════════════════════════════════════
     TRUST BAR
══════════════════════════════════════════ -->
<section style="background:linear-gradient(135deg,#1C1917,#292524);padding:48px 0;">
  <div class="container">
    <div class="trust-bar-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0;border:1px solid rgba(202,138,4,0.15);border-radius:20px;overflow:hidden;">
      <?php
      $trustFeatures = [
        ['Free Delivery','On orders over ₦50,000','M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H3m16.5 0h-1.5m-1.5 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 4.5h19.5M3.75 7.5h16.5'],
        ['Certified Quality','100% authentic stones','M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
        ['Secure Payments','Encrypted transactions','M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z'],
        ['30-Day Returns','Hassle-free policy','M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3'],
      ];
      foreach ($trustFeatures as $idx => [$title, $sub, $icon]):
      ?>
        <div style="padding:28px 24px;display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px;<?php echo $idx>0?'border-left:1px solid rgba(202,138,4,0.15);':''; ?>">
          <div style="width:48px;height:48px;border-radius:14px;background:rgba(202,138,4,0.12);display:flex;align-items:center;justify-content:center;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#CA8A04" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $icon; ?>"/></svg>
          </div>
          <div>
            <div style="font-weight:700;font-size:14px;color:white;margin-bottom:3px;"><?php echo $title; ?></div>
            <div style="font-size:12px;color:rgba(255,255,255,0.50);"><?php echo $sub; ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════
     NEW ARRIVALS
══════════════════════════════════════════ -->
<section style="padding:80px 0;background:var(--white);">
  <div class="container">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:40px;flex-wrap:wrap;gap:16px;">
      <div>
        <p class="section-eyebrow">Just Arrived</p>
        <h2 class="section-title">New Arrivals</h2>
        <div class="section-divider"></div>
      </div>
      <a href="shop.php?sort=newest" class="btn btn-outline">See All New</a>
    </div>

    <?php if (!empty($newArrivals)): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;">
      <?php foreach ($newArrivals as $p): ?>
        <div class="product-card" onclick="window.location='product.php?id=<?php echo $p['id']; ?>'">
          <div class="product-card-img">
            <img src="<?php echo htmlspecialchars($p['image']); ?>"
                 alt="<?php echo htmlspecialchars($p['name']); ?>"
                 loading="lazy"
                 onerror="this.src='https://placehold.co/400x400/F5F5F4/78716C?text=Jewelry'">
            <span class="product-card-badge badge-new">New</span>
            <div class="product-card-actions">
              <button onclick="event.stopPropagation();addToCart(<?php echo $p['id']; ?>)" class="icon-btn" title="Add to Cart">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
              </button>
              <?php if (isLoggedIn()): ?>
              <button onclick="event.stopPropagation();addToWishlist(<?php echo $p['id']; ?>)" class="icon-btn" title="Add to Wishlist">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
              </button>
              <?php endif; ?>
            </div>
          </div>
          <div class="product-card-body">
            <h3 class="product-card-name">
              <a href="product.php?id=<?php echo $p['id']; ?>" onclick="event.stopPropagation()"><?php echo htmlspecialchars($p['name']); ?></a>
            </h3>
            <div class="product-card-price">
              <span class="price-current"><?php echo formatPrice($p['price']); ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══════════════════════════════════════
     BRAND STORY STRIP
══════════════════════════════════════════ -->
<section style="padding:80px 0;background:var(--cream);">
  <div class="container">
    <div class="story-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center;">
      <div style="border-radius:24px;overflow:hidden;aspect-ratio:4/3;box-shadow:var(--shadow-lg);">
        <img src="https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=700&h=520&fit=crop&q=80"
             alt="Jewelry craftsmanship" style="width:100%;height:100%;object-fit:cover;">
      </div>
      <div>
        <p class="section-eyebrow">Our Story</p>
        <h2 class="section-title" style="margin-bottom:16px;">Crafted with Passion,<br><em style="color:var(--gold);font-style:italic;">Worn with Pride</em></h2>
        <p style="font-size:15px;color:var(--stone-mid);line-height:1.80;margin-bottom:20px;">
          Every piece in our collection is carefully curated and certified authentic. We work with master craftspeople to bring you jewelry that carries meaning — and lasts generations.
        </p>
        <p style="font-size:15px;color:var(--stone-mid);line-height:1.80;margin-bottom:32px;">
          From engagement rings to everyday elegance, Phelyz Store is your partner in life's most precious moments.
        </p>
        <a href="about.php" class="btn btn-dark">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
          Learn Our Story
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Page-specific responsive overrides -->
<style>
@media(max-width:1024px){
  #hero-main-grid { grid-template-columns:1fr !important; gap:0 !important; }
  #hero-main-grid > div:last-child { display:none !important; }
  .story-grid { grid-template-columns:1fr !important; gap:28px !important; }
}
@media(max-width:768px){
  .trust-bar-grid { grid-template-columns:repeat(2,1fr) !important; }
  .trust-bar-grid > div { border-left:none !important; border-top:1px solid rgba(202,138,4,0.12) !important; }
  .trust-bar-grid > div:first-child, .trust-bar-grid > div:nth-child(2) { border-top:none !important; }

  /* Center Our Story text column */
  .story-grid > div:last-child { text-align:center !important; }
  .story-grid > div:last-child a.btn { display:inline-flex !important; }
}
@media(max-width:480px){
  .trust-bar-grid { grid-template-columns:1fr !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
