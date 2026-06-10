<?php
$pageTitle = "Checkout";
require_once 'includes/header.php';
require_once 'includes/cart-functions.php';

// Pre-compute state so the summary reflects it on page load
$checkoutState = sanitize($_POST['shipping_state'] ?? $_SESSION['phelyz_shipping_state'] ?? '');
$cartSummary = getCartSummary($checkoutState ?: null);
if (empty($cartSummary['items'])) redirect('cart.php');
$user = isLoggedIn() ? getCurrentUser() : null;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingFirst = sanitize($_POST['shipping_first_name'] ?? '');
    $shippingLast  = sanitize($_POST['shipping_last_name'] ?? '');
    $shippingAddr  = sanitize($_POST['shipping_address'] ?? '');
    $shippingCity  = sanitize($_POST['shipping_city'] ?? '');
    $shippingPhone = sanitize($_POST['shipping_phone'] ?? '');

    $shippingState = sanitize($_POST['shipping_state'] ?? '');
    if ($shippingFirst && $shippingLast && $shippingAddr && $shippingCity && $shippingPhone && $shippingState) {
        $result = processCheckout($_POST);
        if ($result['success']) {
            redirect('order-details.php?id=' . $result['order_id'] . '&success=1');
        }
    }
}
?>

<!-- Steps indicator -->
<div style="background:var(--white);border-bottom:1px solid var(--cream-dark);padding:16px 0;">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:center;gap:0;">
      <?php
      $steps = [['1','Cart'],['2','Shipping & Payment'],['3','Confirmation']];
      foreach ($steps as $i => [$num, $label]):
        $active = $i === 1;
        $done   = $i === 0;
      ?>
        <div style="display:flex;align-items:center;gap:0;">
          <div style="display:flex;align-items:center;gap:8px;padding:0 12px;">
            <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;
              <?php echo $done?'background:var(--gold);color:white;':($active?'background:var(--black);color:white;':'background:var(--cream-dark);color:var(--stone-mid);'); ?>">
              <?php echo $done?'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>':$num; ?>
            </div>
            <span style="font-size:13px;font-weight:<?php echo $active?'700':'500'; ?>;color:<?php echo $active?'var(--black)':($done?'var(--stone-mid)':'var(--stone-mid)'); ?>;"><?php echo $label; ?></span>
          </div>
          <?php if ($i < count($steps)-1): ?>
            <div style="width:40px;height:1px;background:var(--cream-dark);flex-shrink:0;"></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="container" style="padding-top:40px;padding-bottom:64px;">
  <form method="POST" id="checkout-form">
    <div id="checkout-cols" style="display:grid;grid-template-columns:1fr 360px;gap:32px;align-items:flex-start;">

      <!-- ── LEFT: Form ── -->
      <div>

        <!-- Shipping -->
        <div class="card" style="padding:28px;margin-bottom:20px;">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--black);color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">1</div>
            <h2 style="font-family:'Cormorant',serif;font-size:22px;font-weight:700;color:var(--black);">Shipping Address</h2>
          </div>

          <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info" style="margin-bottom:20px;">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
              <span><a href="login.php?redirect=checkout.php" style="font-weight:700;color:var(--gold);">Sign in</a> for faster checkout with saved addresses.</span>
            </div>
          <?php endif; ?>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">First Name *</label>
              <input type="text" name="shipping_first_name" class="form-input"
                     value="<?php echo htmlspecialchars($user['first_name'] ?? ($_POST['shipping_first_name'] ?? '')); ?>" required>
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Last Name *</label>
              <input type="text" name="shipping_last_name" class="form-input"
                     value="<?php echo htmlspecialchars($user['last_name'] ?? ($_POST['shipping_last_name'] ?? '')); ?>" required>
            </div>
          </div>

          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label">Street Address *</label>
            <input type="text" name="shipping_address" class="form-input"
                   placeholder="House number and street name"
                   value="<?php echo htmlspecialchars($_POST['shipping_address'] ?? ''); ?>" required>
          </div>

          <?php
          $coNigStates = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno',
            'Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT (Abuja)','Gombe','Imo',
            'Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger',
            'Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];
          $coSavedState = $checkoutState ?: ($user['state'] ?? ($_POST['shipping_state'] ?? ''));
          ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">City *</label>
              <input type="text" name="shipping_city" class="form-input"
                     value="<?php echo htmlspecialchars($user['city'] ?? ($_POST['shipping_city'] ?? '')); ?>" required>
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">State *</label>
              <select name="shipping_state" id="co-state-select" required class="form-input form-select"
                      onchange="updateCheckoutShipping(this.value)">
                <option value="">Select State</option>
                <?php foreach ($coNigStates as $st): ?>
                  <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $coSavedState===$st?'selected':''; ?>><?php echo htmlspecialchars($st); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Phone *</label>
              <input type="tel" name="shipping_phone" class="form-input"
                     placeholder="+234 000 000 0000"
                     value="<?php echo htmlspecialchars($user['phone'] ?? ($_POST['shipping_phone'] ?? '')); ?>" required>
            </div>
          </div>

          <div class="form-group" style="margin:0;">
            <label class="form-label">Order Notes <span style="color:var(--stone-mid);font-weight:400;">(optional)</span></label>
            <textarea name="notes" class="form-input" style="min-height:80px;resize:vertical;"
                      placeholder="Special delivery instructions or notes…"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="card" style="padding:28px;">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--black);color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">2</div>
            <h2 style="font-family:'Cormorant',serif;font-size:22px;font-weight:700;color:var(--black);">Payment Method</h2>
          </div>

          <div style="display:flex;flex-direction:column;gap:12px;">

            <!-- Cash on Delivery -->
            <label style="display:flex;align-items:flex-start;gap:14px;padding:16px 18px;border:1.5px solid var(--cream-dark);border-radius:10px;cursor:pointer;transition:border-color 0.2s;" id="cod-label"
                   onmouseover="this.style.borderColor='var(--gold)'" onmouseout="updatePaymentBorder()">
              <input type="radio" name="payment_method" value="cod" checked
                     style="accent-color:var(--gold);margin-top:2px;width:16px;height:16px;flex-shrink:0;"
                     onchange="updatePaymentBorder()">
              <div style="display:flex;align-items:flex-start;gap:12px;flex:1;">
                <div style="width:40px;height:40px;border-radius:8px;background:rgba(202,138,4,0.10);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#CA8A04" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/></svg>
                </div>
                <div>
                  <div style="font-size:14px;font-weight:700;color:var(--black);margin-bottom:2px;">Cash on Delivery</div>
                  <div style="font-size:12px;color:var(--stone-mid);">Pay with cash when your order arrives at your door.</div>
                </div>
              </div>
            </label>

            <!-- Bank Transfer -->
            <label style="display:flex;align-items:flex-start;gap:14px;padding:16px 18px;border:1.5px solid var(--cream-dark);border-radius:10px;cursor:pointer;transition:border-color 0.2s;" id="bank-label"
                   onmouseover="this.style.borderColor='var(--gold)'" onmouseout="updatePaymentBorder()">
              <input type="radio" name="payment_method" value="bank_transfer"
                     style="accent-color:var(--gold);margin-top:2px;width:16px;height:16px;flex-shrink:0;"
                     onchange="updatePaymentBorder()">
              <div style="display:flex;align-items:flex-start;gap:12px;flex:1;">
                <div style="width:40px;height:40px;border-radius:8px;background:rgba(59,130,246,0.10);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#3B82F6" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z"/></svg>
                </div>
                <div>
                  <div style="font-size:14px;font-weight:700;color:var(--black);margin-bottom:2px;">Bank Transfer</div>
                  <div style="font-size:12px;color:var(--stone-mid);">Direct transfer to our corporate account. We'll confirm your order once payment clears.</div>
                </div>
              </div>
            </label>

          </div>
        </div>
      </div>

      <!-- ── RIGHT: Order Summary ── -->
      <div style="position:sticky;top:calc(var(--nav-height) + 16px);">
        <div class="card" style="padding:24px;margin-bottom:16px;">
          <h3 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;color:var(--black);margin-bottom:20px;">Order Summary</h3>

          <!-- Items -->
          <div style="display:flex;flex-direction:column;gap:12px;padding-bottom:16px;border-bottom:1px solid var(--cream-dark);margin-bottom:16px;">
            <?php foreach ($cartSummary['items'] as $item): ?>
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="position:relative;flex-shrink:0;">
                  <img src="<?php echo htmlspecialchars($item['image']); ?>"
                       alt="<?php echo htmlspecialchars($item['name']); ?>"
                       style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid var(--cream-dark);"
                       onerror="this.src='https://placehold.co/48x48/F5F5F4/78716C?text=J'">
                  <span style="position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;background:var(--stone);color:white;border-radius:99px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;"><?php echo $item['quantity']; ?></span>
                </div>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:13px;font-weight:600;color:var(--black);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($item['name']); ?></div>
                  <div style="font-size:12px;color:var(--stone-mid);"><?php echo formatPrice($item['price']); ?> each</div>
                </div>
                <div style="font-size:13px;font-weight:700;color:var(--black);flex-shrink:0;"><?php echo formatPrice($item['price'] * $item['quantity']); ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Totals -->
          <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;">
              <span style="color:var(--stone-mid);">Subtotal</span>
              <span style="font-weight:600;color:var(--black);"><?php echo formatPrice($cartSummary['subtotal']); ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;">
              <span style="color:var(--stone-mid);">Shipping<?php if ($coSavedState): ?> <span style="font-size:11px;">(<?php echo htmlspecialchars($coSavedState); ?>)</span><?php endif; ?></span>
              <span id="co-shipping-display" style="font-weight:600;color:<?php echo $cartSummary['shipping']==0?'#22C55E':'var(--black)'; ?>;"><?php echo $cartSummary['shipping']==0?'FREE':formatPrice($cartSummary['shipping']); ?></span>
            </div>
            <?php if (!empty($cartSummary['tax']) && $cartSummary['tax'] > 0): ?>
            <div style="display:flex;justify-content:space-between;font-size:13px;">
              <span style="color:var(--stone-mid);">Tax</span>
              <span style="font-weight:600;color:var(--black);"><?php echo formatPrice($cartSummary['tax']); ?></span>
            </div>
            <?php endif; ?>
          </div>

          <!-- Total -->
          <div style="display:flex;justify-content:space-between;padding-top:16px;border-top:2px solid var(--black);margin-bottom:20px;">
            <span style="font-weight:700;font-size:15px;color:var(--black);">Total</span>
            <span style="font-family:'Cormorant',serif;font-size:24px;font-weight:700;color:var(--black);"><?php echo formatPrice($cartSummary['total']); ?></span>
          </div>

          <button type="submit" class="btn btn-gold btn-full" style="font-size:15px;padding:15px 28px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Place Order
          </button>

          <div style="text-align:center;margin-top:14px;display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;color:var(--stone-mid);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            Secure Encrypted Checkout
          </div>
        </div>

        <a href="cart.php" style="display:flex;align-items:center;justify-content:center;gap:6px;font-size:13px;color:var(--stone-mid);text-decoration:none;padding:8px 0;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--stone-mid)'">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
          Return to Cart
        </a>
      </div>
    </div>
  </form>
</div>

<style>
@media(max-width:900px){
  #checkout-cols { grid-template-columns:1fr !important; }
  #checkout-cols > div:last-child { position:static !important; }
}
@media(max-width:480px){
  /* Compact checkout steps on tiny phones */
  #checkout-cols .card { padding:18px !important; }
}
</style>

<script>
function updateCheckoutShipping(state) {
  if (!state) return;
  var display = document.getElementById('co-shipping-display');
  if (display) display.innerHTML = '<span style="color:var(--stone-mid);font-size:12px;">…</span>';
  fetch('/api/get-shipping-rate.php?state=' + encodeURIComponent(state))
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (!d.success || !display) return;
      display.style.color = d.is_free ? '#22C55E' : 'var(--black)';
      display.textContent = d.is_free ? 'FREE' : d.formatted;
      // Update label
      var row = display.closest('[style*="justify-content:space-between"]');
      if (row) {
        var lbl = row.querySelector('span:first-child');
        if (lbl) lbl.innerHTML = 'Shipping <span style="font-size:11px;">(' + d.state + ')</span>';
      }
    })
    .catch(function() {});
}

// Auto-fire on page load if state already selected
(function() {
  var sel = document.getElementById('co-state-select');
  if (sel && sel.value) updateCheckoutShipping(sel.value);
})();

function updatePaymentBorder(){
  var selected = document.querySelector('input[name="payment_method"]:checked')?.value;
  document.getElementById('cod-label').style.borderColor  = selected==='cod'         ?'var(--gold)':'var(--cream-dark)';
  document.getElementById('bank-label').style.borderColor = selected==='bank_transfer'?'var(--gold)':'var(--cream-dark)';
}
updatePaymentBorder();
document.querySelectorAll('input[name="payment_method"]').forEach(r=>r.addEventListener('change',updatePaymentBorder));
</script>

<?php require_once 'includes/footer.php'; ?>
