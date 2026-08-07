<?php
$pageTitle = "Promo Pack";
require_once 'includes/header.php';

$db = getDB();
$categories = function_exists('getAllCategories') ? getAllCategories(false) : [];

// Load products (id, name, price, image, category) for the picker
$products = $db->fetchAll(
    "SELECT p.id, p.name, p.price, p.image, p.compare_price, c.name AS category_name
     FROM products p LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.is_active = 1
     ORDER BY p.created_at DESC"
);

// Data the JS needs
$productsJs = array_map(function ($p) {
    return [
        'id'        => (int)$p['id'],
        'name'      => $p['name'],
        'price'     => formatPrice($p['price']),
        'raw_price' => (float)$p['price'],
        'compare'   => ($p['compare_price'] > $p['price']) ? formatPrice($p['compare_price']) : '',
        'image'     => $p['image'],
        'category'  => $p['category_name'] ?? '',
        'link'      => SITE_URL . '/product.php?id=' . (int)$p['id'],
    ];
}, $products);

$waNumber = preg_replace('/\D/', '', defined('SITE_WHATSAPP') ? SITE_WHATSAPP : '');
?>

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <div style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--gold);margin-bottom:4px;">Image Studio</div>
    <h2 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0 0 6px;">Promo Pack - WhatsApp Status</h2>
    <p style="font-size:13px;color:var(--stone-mid);margin:0;max-width:680px;">Pick the products you want to promote. The studio builds a ready-to-post 1080×1920 status image for each (photo + name + price + your branding) and a matching caption with the product link. Download the pack to your phone, then post each status in seconds - no manual editing, captioning, or link-copying.</p>
  </div>
</div>

<!-- Toolbar -->
<div class="card" style="padding:16px 20px;margin-bottom:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
  <input type="search" id="promo-search" placeholder="Search products…" oninput="filterPromo(this.value)"
         class="form-input" style="flex:1;min-width:200px;margin:0;">
  <select id="promo-category" onchange="filterPromoCat(this.value)" class="form-input form-select" style="max-width:200px;margin:0;">
    <option value="">All categories</option>
    <?php foreach ($categories as $c): ?>
    <option value="<?php echo htmlspecialchars($c['name'] ?? ''); ?>"><?php echo htmlspecialchars($c['name'] ?? ''); ?></option>
    <?php endforeach; ?>
  </select>
  <button type="button" onclick="promoSelectAllVisible()" class="btn btn-outline btn-sm">Select visible</button>
  <button type="button" onclick="promoClear()" class="btn btn-outline btn-sm">Clear</button>
  <span id="promo-count" style="font-size:13px;color:var(--stone-mid);font-weight:600;">0 selected</span>
</div>

<!-- Style controls -->
<div class="card" style="padding:16px 20px;margin-bottom:16px;">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;align-items:end;">
    <div class="form-group" style="margin:0;">
      <label class="form-label">Theme</label>
      <select id="promo-theme" class="form-input form-select">
        <option value="dark">Dark luxe (black + gold)</option>
        <option value="cream">Cream editorial</option>
        <option value="gold">Gold gradient</option>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label">Headline text</label>
      <input type="text" id="promo-headline" class="form-input" value="✨ New In" placeholder="e.g., Now Available">
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label">Call to action</label>
      <input type="text" id="promo-cta" class="form-input" value="Tap the link to order 🛍️" placeholder="Order now…">
    </div>
    <div class="form-group" style="margin:0;">
      <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid var(--cream-dark);border-radius:8px;cursor:pointer;font-size:13px;">
        <input type="checkbox" id="promo-show-price" checked style="accent-color:var(--gold);"> Show price
      </label>
    </div>
  </div>
</div>

<!-- Product grid -->
<div class="card" style="padding:20px;margin-bottom:16px;">
  <div id="promo-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px;">
    <?php foreach ($products as $p): ?>
      <div class="promo-item" data-id="<?php echo (int)$p['id']; ?>"
           data-name="<?php echo htmlspecialchars(strtolower($p['name'] ?? '')); ?>"
           data-cat="<?php echo htmlspecialchars($p['category_name'] ?? ''); ?>"
           onclick="togglePromo(<?php echo (int)$p['id']; ?>)"
           style="border:2px solid var(--cream-dark);border-radius:12px;overflow:hidden;background:white;cursor:pointer;transition:border-color 0.15s;position:relative;">
        <div style="position:relative;">
          <img src="<?php echo htmlspecialchars(productImageUrl($p['image'])); ?>" alt=""
               style="width:100%;height:150px;object-fit:cover;display:block;"
               onerror="this.src='https://placehold.co/150x150/F5F5F4/78716C?text=J'">
          <span class="promo-check" style="position:absolute;top:8px;right:8px;width:24px;height:24px;border-radius:50%;background:white;border:2px solid var(--cream-dark);display:none;align-items:center;justify-content:center;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="white" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
          </span>
        </div>
        <div style="padding:8px 10px;">
          <div style="font-size:12px;font-weight:600;color:var(--black);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($p['name'] ?? ''); ?></div>
          <div style="font-size:12px;color:var(--gold);font-weight:700;"><?php echo formatPrice($p['price']); ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (empty($products)): ?>
    <p style="color:var(--stone-mid);font-size:14px;margin:0;">No active products yet.</p>
  <?php endif; ?>
</div>

<!-- Generate bar -->
<div style="position:sticky;bottom:0;background:white;border-top:1px solid var(--cream-dark);padding:16px 0;display:flex;gap:12px;flex-wrap:wrap;align-items:center;z-index:5;">
  <button type="button" onclick="promoGenerate()" id="promo-generate-btn" class="btn btn-gold" disabled style="opacity:0.5;">
    Generate Promo Pack
  </button>
  <span style="font-size:12px;color:var(--stone-mid);">Builds status images in your browser - nothing is uploaded.</span>
</div>

<!-- Output modal -->
<div id="promo-output" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;overflow:auto;padding:20px;">
  <div style="max-width:960px;margin:20px auto;background:white;border-radius:14px;overflow:hidden;">
    <div style="padding:18px 22px;border-bottom:1px solid var(--cream-dark);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
      <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;margin:0;">Your Promo Pack</h3>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" onclick="promoDownloadAll()" class="btn btn-gold btn-sm">⬇ Download all images</button>
        <button type="button" onclick="promoCopyCaptions()" class="btn btn-outline btn-sm">📋 Copy all captions</button>
        <button type="button" onclick="document.getElementById('promo-output').style.display='none'" class="btn btn-outline btn-sm">Close</button>
      </div>
    </div>
    <div style="padding:20px;">
      <p style="font-size:13px;color:var(--stone-mid);margin:0 0 16px;line-height:1.5;">
        On your phone: download all images to your gallery, then in WhatsApp → Status → add each image and paste its caption. Tip: WhatsApp status doesn't make links clickable, but customers can tap-hold to copy, or reply to your status.
      </p>
      <div id="promo-results" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:18px;"></div>
    </div>
  </div>
</div>

<script>
window.PROMO_PRODUCTS = <?php echo json_encode($productsJs); ?>;
window.PROMO_STORE = {
  name: <?php echo json_encode(SITE_NAME); ?>,
  url:  <?php echo json_encode(SITE_URL); ?>,
  wa:   <?php echo json_encode($waNumber); ?>
};
</script>
<script src="<?php echo SITE_URL; ?>/assets/js/promo-pack.js?v=1"></script>

<?php require_once 'includes/footer.php'; ?>
