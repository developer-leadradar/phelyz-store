<?php
$pageTitle = "Shopping Cart";
require_once 'includes/header.php';
require_once 'includes/cart-functions.php';

$cartSummary = getCartSummary();
$items = $cartSummary['items'];
?>

<div class="bg-cream min-h-screen">
  <div class="container py-10">

    <!-- Page Title -->
    <div class="mb-8">
      <nav class="breadcrumb mb-3">
        <a href="index.php">Home</a>
        <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" class="text-stone-400"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
        <span>Cart</span>
      </nav>
      <h1 class="font-display text-3xl md:text-4xl font-semibold text-stone-900">
        Shopping Cart
        <?php if (!empty($items)): ?>
          <span class="text-lg font-sans font-normal text-stone-400 ml-2">(<?php echo $cartSummary['item_count']; ?> item<?php echo $cartSummary['item_count'] != 1 ? 's' : ''; ?>)</span>
        <?php endif; ?>
      </h1>
    </div>

    <?php if (empty($items)): ?>
      <!-- ── Empty Cart ──────────────────────────────────── -->
      <div class="flex flex-col items-center justify-center py-24 text-center">
        <div class="w-24 h-24 rounded-full bg-stone-100 flex items-center justify-center mb-6">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40" class="text-stone-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
          </svg>
        </div>
        <h2 class="font-display text-2xl font-semibold text-stone-800 mb-2">Your cart is empty</h2>
        <p class="text-stone-500 mb-8 max-w-sm">Add some beautiful jewellery pieces to your cart and come back here to checkout.</p>
        <a href="shop.php" class="btn btn-gold flex items-center gap-2">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
          Start Shopping
        </a>
      </div>

    <?php else: ?>
      <!-- ── Cart Layout ─────────────────────────────────── -->
      <div class="flex flex-col lg:flex-row gap-8 items-start">

        <!-- Left: Cart Items (80%) -->
        <div class="flex-1 min-w-0 w-full">

          <!-- List header row -->
          <div class="hidden md:grid grid-cols-12 gap-4 px-4 pb-3 border-b border-stone-200 text-xs font-semibold text-stone-400 uppercase tracking-wider">
            <div class="col-span-6">Product</div>
            <div class="col-span-2 text-center">Price</div>
            <div class="col-span-2 text-center">Quantity</div>
            <div class="col-span-1 text-right">Subtotal</div>
            <div class="col-span-1"></div>
          </div>

          <!-- Items -->
          <div class="divide-y divide-stone-100">
            <?php foreach ($items as $item): ?>
              <div class="cart-item py-5 px-0 md:px-2" id="cart-item-<?php echo (int)$item['id']; ?>">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">

                  <!-- Product (image + name) -->
                  <div class="md:col-span-6 flex items-center gap-4">
                    <a href="product.php?id=<?php echo (int)$item['product_id']; ?>"
                       class="flex-shrink-0 w-20 h-20 md:w-24 md:h-24 rounded-xl overflow-hidden bg-stone-100 border border-stone-200">
                      <img src="<?php echo htmlspecialchars($item['image']); ?>"
                           alt="<?php echo htmlspecialchars($item['name']); ?>"
                           class="w-full h-full object-contain p-1">
                    </a>
                    <div class="min-w-0">
                      <a href="product.php?id=<?php echo (int)$item['product_id']; ?>"
                         class="font-semibold text-stone-800 text-sm leading-snug hover:text-gold transition-colors line-clamp-2 block mb-1">
                        <?php echo htmlspecialchars($item['name']); ?>
                      </a>
                      <?php if ($item['stock_quantity'] < $item['quantity']): ?>
                        <span class="inline-flex items-center gap-1 text-xs text-amber-600 font-medium">
                          <svg viewBox="0 0 20 20" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                          Only <?php echo (int)$item['stock_quantity']; ?> in stock
                        </span>
                      <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                          <svg viewBox="0 0 20 20" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                          In Stock
                        </span>
                      <?php endif; ?>
                      <!-- Mobile price -->
                      <p class="text-sm font-bold text-stone-800 mt-1 md:hidden"><?php echo formatPrice($item['price']); ?></p>
                    </div>
                  </div>

                  <!-- Unit price (desktop) -->
                  <div class="hidden md:block md:col-span-2 text-center">
                    <span class="text-sm font-semibold text-stone-700"><?php echo formatPrice($item['price']); ?></span>
                  </div>

                  <!-- Quantity -->
                  <div class="md:col-span-2 flex md:justify-center">
                    <div class="qty-stepper">
                      <button onclick="changeQty(this, -1)" class="qty-btn" aria-label="Decrease quantity">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                      </button>
                      <input type="number"
                             class="qty-input"
                             value="<?php echo (int)$item['quantity']; ?>"
                             min="1"
                             max="<?php echo (int)$item['stock_quantity']; ?>"
                             onchange="updateQuantity(<?php echo (int)$item['id']; ?>, this.value)"
                             data-item-id="<?php echo (int)$item['id']; ?>"
                             data-max="<?php echo (int)$item['stock_quantity']; ?>">
                      <button onclick="changeQty(this, 1)" class="qty-btn" aria-label="Increase quantity">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                      </button>
                    </div>
                  </div>

                  <!-- Subtotal -->
                  <div class="md:col-span-1 md:text-right flex md:block items-center gap-2">
                    <span class="text-xs text-stone-400 md:hidden">Subtotal:</span>
                    <span class="font-bold text-stone-900 text-sm"><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                  </div>

                  <!-- Remove -->
                  <div class="md:col-span-1 flex md:justify-end">
                    <button onclick="removeFromCart(<?php echo (int)$item['id']; ?>)"
                      class="icon-btn text-stone-400 hover:text-red-500" title="Remove item" aria-label="Remove item">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>

                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Bottom actions -->
          <div style="padding-top:20px;margin-top:8px;border-top:1px solid #e7e5e4;">
            <a href="shop.php" class="btn btn-outline btn-sm" style="display:flex;align-items:center;justify-content:center;gap:6px;width:100%;margin-bottom:12px;">
              <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              Continue Shopping
            </a>
            <button onclick="clearCart()"
              style="display:flex;align-items:center;justify-content:center;gap:6px;width:100%;font-size:13px;color:#EF4444;font-weight:600;background:none;border:1px solid #fecaca;border-radius:8px;cursor:pointer;padding:10px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              Clear Cart
            </button>
          </div>
        </div>

        <!-- Right: Order Summary (sticky) -->
        <div class="w-full lg:w-80 flex-shrink-0">
          <div class="card p-6 lg:sticky lg:top-24">
            <h3 class="font-display text-xl font-semibold text-stone-900 mb-5">Order Summary</h3>

            <!-- State selector for shipping estimate -->
            <?php
            $nigStatesCart = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno',
              'Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT (Abuja)','Gombe','Imo',
              'Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger',
              'Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];
            $cartSavedState = $_SESSION['phelyz_shipping_state'] ?? '';
            ?>
            <div style="margin-bottom:16px;">
              <label style="font-size:11px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--stone-mid);display:block;margin-bottom:6px;">Deliver to</label>
              <select id="cart-state-select" onchange="updateCartShipping(this.value)"
                      style="width:100%;padding:9px 12px;border:1.5px solid var(--cream-dark);border-radius:8px;font-size:13px;font-family:inherit;outline:none;background:white;"
                      onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='var(--cream-dark)'">
                <option value="">Select your state</option>
                <?php foreach ($nigStatesCart as $st): ?>
                  <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $cartSavedState===$st?'selected':''; ?>><?php echo htmlspecialchars($st); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Line items -->
            <div class="space-y-2 mb-5 pb-5 border-b border-stone-100">
              <div class="flex justify-between text-sm text-stone-600">
                <span>Subtotal (<?php echo $cartSummary['item_count']; ?> item<?php echo $cartSummary['item_count'] != 1 ? 's' : ''; ?>)</span>
                <span class="font-semibold text-stone-800"><?php echo formatPrice($cartSummary['subtotal']); ?></span>
              </div>

              <?php if (isset($cartSummary['tax']) && $cartSummary['tax'] > 0): ?>
              <div class="flex justify-between text-sm text-stone-600">
                <span>Tax (5%)</span>
                <span class="font-semibold text-stone-800"><?php echo formatPrice($cartSummary['tax']); ?></span>
              </div>
              <?php endif; ?>

              <div class="flex justify-between text-sm text-stone-600">
                <span>Shipping <?php if ($cartSavedState): ?><span style="font-size:11px;color:var(--stone-mid);">(<?php echo htmlspecialchars($cartSavedState); ?>)</span><?php endif; ?></span>
                <span id="cart-shipping-display" class="font-semibold">
                  <?php if ($cartSummary['shipping'] == 0): ?>
                    <span class="text-emerald-600 font-bold">FREE</span>
                  <?php else: ?>
                    <span class="text-stone-800"><?php echo formatPrice($cartSummary['shipping']); ?></span>
                  <?php endif; ?>
                </span>
              </div>

              <?php if ($cartSummary['subtotal'] < $cartSummary['threshold'] && $cartSummary['subtotal'] > 0): ?>
                <div class="bg-gold-pale border border-gold/30 rounded-lg px-3 py-2 mt-3">
                  <p class="text-xs text-stone-700">
                    <span class="font-bold text-gold">+<?php echo formatPrice($cartSummary['threshold'] - $cartSummary['subtotal']); ?></span>
                    more to get <span class="font-bold">free shipping</span>
                  </p>
                  <div class="mt-1.5 h-1.5 bg-stone-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gold rounded-full" style="width:<?php echo round($cartSummary['subtotal'] / $cartSummary['threshold'] * 100); ?>%"></div>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Total -->
            <div class="flex justify-between items-center mb-6">
              <span class="font-bold text-stone-900">Total</span>
              <span class="font-display text-2xl font-bold text-stone-900"><?php echo formatPrice($cartSummary['total']); ?></span>
            </div>

            <!-- CTA -->
            <a href="checkout.php" class="btn btn-gold btn-full" style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:12px;font-size:15px;padding:16px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              Proceed to Checkout
            </a>

            <a href="shop.php" class="block text-center text-sm text-stone-400 hover:text-stone-600 transition-colors">
              or continue shopping
            </a>

            <!-- Trust signals -->
            <div class="mt-5 pt-5 border-t border-stone-100 space-y-2">
              <div class="flex items-center gap-2 text-xs text-stone-500">
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" class="text-emerald-500 flex-shrink-0"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                SSL secure checkout
              </div>
              <div class="flex items-center gap-2 text-xs text-stone-500">
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" class="text-gold flex-shrink-0"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                Cash on delivery available
              </div>
              <div class="flex items-center gap-2 text-xs text-stone-500">
                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" class="text-gold flex-shrink-0"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg>
                30-day easy returns
              </div>
            </div>
          </div>
        </div>

      </div>
    <?php endif; ?>

  </div>
</div>

<style>
/* ── Mobile cart item layout ── */
@media (max-width: 767px) {
  /* Convert grid to flex-wrap so product takes full row, controls share next row */
  .cart-item > div {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
    align-items: center !important;
  }
  /* Product info: full width */
  .cart-item > div > div:first-child {
    flex: 1 1 100% !important;
  }
  /* Unit-price cell: already hidden by Tailwind .hidden */
  /* Qty stepper: auto width */
  .cart-item > div > div:nth-child(3) {
    flex: 0 0 auto !important;
  }
  /* Subtotal: push to right, hide "Subtotal:" label since price is in product row */
  .cart-item > div > div:nth-child(4) {
    flex: 1 1 auto !important;
    justify-content: flex-end !important;
    display: flex !important;
  }
  /* Remove button: stays at end */
  .cart-item > div > div:nth-child(5) {
    flex: 0 0 auto !important;
    margin-left: 0 !important;
  }
  /* Hide the mobile "Subtotal:" label — price already shows in product section */
  .cart-item .text-xs.text-stone-400 { display: none !important; }

  /* Cart page title: center on mobile */
  .cart-item + .cart-item { border-top: 1px solid #f1f0ef; }
}
</style>


<script>
function updateCartShipping(state) {
  if (!state) return;
  var display = document.getElementById('cart-shipping-display');
  if (display) display.innerHTML = '<span style="color:var(--stone-mid);font-size:12px;">Calculating…</span>';
  fetch('/api/get-shipping-rate.php?state=' + encodeURIComponent(state))
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (!d.success) return;
      if (display) {
        display.innerHTML = d.is_free
          ? '<span class="text-emerald-600 font-bold">FREE</span>'
          : '<span class="text-stone-800">' + d.formatted + '</span>';
      }
      // Update the label to show selected state
      var shippingLabel = display ? display.closest('.flex').querySelector('span:first-child') : null;
      if (shippingLabel) {
        shippingLabel.innerHTML = 'Shipping <span style="font-size:11px;color:var(--stone-mid);">(' + d.state + ')</span>';
      }
    })
    .catch(function() {});
}

// Auto-fire for saved state
(function() {
  var sel = document.getElementById('cart-state-select');
  if (sel && sel.value) updateCartShipping(sel.value);
})();
</script>

<?php require_once 'includes/footer.php'; ?>
