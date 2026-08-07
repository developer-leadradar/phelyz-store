<?php
$pageTitle = "Checkout";
require_once 'includes/header.php';
require_once 'includes/cart-functions.php';
require_once 'includes/paystack.php';

// If a card payment completed while they were away from the site, the cart is
// stale - empty it before deciding whether there is anything to check out.
cartSyncPendingOrder();

// Pre-compute state so the summary reflects it on page load
$checkoutState = sanitize($_POST['shipping_state'] ?? $_SESSION['phelyz_shipping_state'] ?? '');
$cartSummary = getCartSummary($checkoutState ?: null);
if (empty($cartSummary['items'])) redirect('cart.php');
$user = isLoggedIn() ? getCurrentUser() : null;

// All payments go through Paystack (card, bank transfer and USSD are all
// handled inside Paystack's own checkout).
$paystackReady = paystackConfigured();
$checkoutError = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingFirst = sanitize($_POST['shipping_first_name'] ?? '');
    $shippingLast  = sanitize($_POST['shipping_last_name'] ?? '');
    $shippingAddr  = sanitize($_POST['shipping_address'] ?? '');
    $shippingCity  = sanitize($_POST['shipping_city'] ?? '');
    $shippingPhone = sanitize($_POST['shipping_phone'] ?? '');

    $shippingState = sanitize($_POST['shipping_state'] ?? '');
    $customerEmail = sanitize($_POST['email'] ?? '');
    if ($shippingFirst && $shippingLast && $shippingAddr && $shippingCity && $shippingPhone && $shippingState) {
        // Paystack is the only payment route.
        $chosenMethod  = 'paystack';
        $methodAllowed = paystackConfigured();

        if (!$methodAllowed) {
            $checkoutError = 'Online payment is temporarily unavailable. Please contact us on WhatsApp to complete your order.';
        } elseif (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $methodAllowed = false;
            $checkoutError = 'Please enter a valid email address so we can send your payment receipt.';
        }

        if ($methodAllowed) {
            $_POST['payment_method'] = 'paystack';
            $result = processCheckout($_POST);
            if ($result['success']) {
                {
                    // Look up the order total, then hand off to Paystack
                    $db = getDB();
                    $order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [(int)$result['order_id']]);
                    $callback = SITE_URL . '/paystack-callback.php?order=' . (int)$result['order_id'];
                    $init = paystackInitialize($customerEmail, (float)$order['total'], $callback, [
                        'order_id'     => (int)$result['order_id'],
                        'order_number' => $result['order_number'],
                    ]);
                    if ($init['ok']) {
                        try {
                            $db->update('orders', ['payment_reference' => $init['reference']], 'id = ?', [(int)$result['order_id']]);
                        } catch (Exception $e) { /* column may not exist yet - non-fatal */ }
                        redirect($init['authorization_url']);
                    }
                    // Init failed - order exists as pending; tell the user
                    $checkoutError = 'Could not start payment: ' . ($init['message'] ?? 'unknown error')
                                   . ' Your order #' . $result['order_number'] . ' was saved - please contact us on WhatsApp to complete it.';
                }
            }
        }
    } elseif (!empty($_POST)) {
        $checkoutError = $checkoutError ?: 'Please fill in all required fields.';
    }
}
?>

<!-- Steps indicator -->
<div class="co-steps-bar">
  <div class="container">
    <div class="co-steps">
      <?php
      // The short label is what shows on a phone. "Shipping & Payment" spelled
      // out needs more room than a 412px screen has, and used to be clipped.
      $steps = [['1','Cart','Cart'],['2','Shipping & Payment','Shipping'],['3','Confirmation','Done']];
      foreach ($steps as $i => [$num, $label, $short]):
        $active = $i === 1;
        $done   = $i === 0;
        $state  = $done ? 'is-done' : ($active ? 'is-active' : '');
      ?>
        <div class="co-step <?php echo $state; ?>">
          <div class="co-step-num">
            <?php echo $done?'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>':$num; ?>
          </div>
          <span class="co-step-label co-step-long"><?php echo $label; ?></span>
          <span class="co-step-label co-step-short"><?php echo $short; ?></span>
        </div>
        <?php if ($i < count($steps)-1): ?>
          <div class="co-step-line"></div>
        <?php endif; ?>
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
            <div class="form-group" style="margin:0;">
              <label class="form-label">Email *</label>
              <input type="email" name="email" class="form-input"
                     placeholder="you@example.com"
                     value="<?php echo htmlspecialchars($user['email'] ?? ($_POST['email'] ?? '')); ?>" required>
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

          <?php if (!empty($checkoutError)): ?>
          <div class="alert alert-error" style="margin-bottom:16px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/></svg>
            <?php echo htmlspecialchars($checkoutError); ?>
          </div>
          <?php endif; ?>

          <?php if (!$paystackReady): ?>
          <div class="alert alert-error" style="margin:0;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            Online payment is temporarily unavailable. Please contact us on WhatsApp to complete your order.
          </div>
          <?php else: ?>

          <input type="hidden" name="payment_method" value="paystack">

          <div style="border:1.5px solid var(--gold);border-radius:12px;padding:20px;background:rgba(202,138,4,0.04);">
            <div style="display:flex;align-items:flex-start;gap:14px;">
              <div style="width:44px;height:44px;border-radius:10px;background:rgba(34,197,94,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#16A34A" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
              </div>
              <div style="flex:1;">
                <div style="font-size:15px;font-weight:700;color:var(--black);margin-bottom:4px;">
                  Secure Online Payment
                  <span style="font-size:10px;font-weight:700;color:#16A34A;background:rgba(34,197,94,0.12);padding:2px 8px;border-radius:99px;margin-left:6px;vertical-align:middle;">Instant</span>
                </div>
                <div style="font-size:13px;color:var(--stone-mid);line-height:1.6;">
                  You'll be taken to Paystack to complete payment. Choose <strong style="color:var(--black);">card, bank transfer or USSD</strong> there - your order is confirmed the moment payment clears.
                </div>
                <div style="display:flex;align-items:center;gap:14px;margin-top:14px;flex-wrap:wrap;">
                  <?php foreach ([['Card','M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z'],['Bank Transfer','M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18'],['USSD','M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3']] as [$lbl,$ic]): ?>
                  <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--stone);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="var(--gold)" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $ic; ?>"/></svg>
                    <?php echo $lbl; ?>
                  </span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:14px;font-size:11px;color:var(--stone-mid);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            We never see or store your card details
          </div>

          <?php endif; ?>
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
                  <img src="<?php echo htmlspecialchars($item['image'] ?? ''); ?>"
                       alt="<?php echo htmlspecialchars($item['name'] ?? ''); ?>"
                       style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid var(--cream-dark);"
                       onerror="this.src='https://placehold.co/48x48/F5F5F4/78716C?text=J'">
                  <span style="position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;background:var(--stone);color:white;border-radius:99px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;"><?php echo $item['quantity']; ?></span>
                </div>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:13px;font-weight:600;color:var(--black);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($item['name'] ?? ''); ?></div>
                  <?php if (!empty($item['selected_color'])): ?>
                  <div style="font-size:11px;color:var(--stone-mid);">Colour: <?php echo htmlspecialchars($item['selected_color'] ?? ''); ?></div>
                  <?php endif; ?>
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
            <div id="co-discount-row" style="display:<?php echo !empty($cartSummary['discount']) ? 'flex' : 'none'; ?>;justify-content:space-between;font-size:13px;">
              <span style="color:#15803D;">Discount <span id="co-discount-code" style="font-size:11px;font-weight:700;"><?php echo htmlspecialchars($cartSummary['coupon_code'] ?? ''); ?></span></span>
              <span id="co-discount-display" style="font-weight:600;color:#15803D;">-<?php echo formatPrice($cartSummary['discount'] ?? 0); ?></span>
            </div>
            <?php if (!empty($cartSummary['tax']) && $cartSummary['tax'] > 0): ?>
            <div style="display:flex;justify-content:space-between;font-size:13px;">
              <span style="color:var(--stone-mid);">Tax</span>
              <span style="font-weight:600;color:var(--black);"><?php echo formatPrice($cartSummary['tax']); ?></span>
            </div>
            <?php endif; ?>
          </div>

          <!-- Coupon -->
          <div style="margin-bottom:16px;padding:14px;background:var(--cream);border:1px solid var(--cream-dark);border-radius:10px;">
            <?php if (!empty($cartSummary['coupon_notice'])): ?>
              <p style="font-size:12px;color:#B91C1C;margin:0 0 10px;"><?php echo htmlspecialchars($cartSummary['coupon_notice'] ?? ''); ?></p>
            <?php endif; ?>

            <div id="coupon-applied" style="display:<?php echo !empty($cartSummary['coupon_code']) ? 'flex' : 'none'; ?>;align-items:center;justify-content:space-between;gap:10px;">
              <span style="font-size:13px;color:var(--black);font-weight:600;">
                <span style="display:inline-block;background:#DCFCE7;color:#15803D;padding:3px 9px;border-radius:6px;font-size:12px;font-weight:700;letter-spacing:0.04em;" id="coupon-applied-code"><?php echo htmlspecialchars($cartSummary['coupon_code'] ?? ''); ?></span>
                applied
              </span>
              <button type="button" onclick="removeCoupon()" style="background:none;border:none;color:#B91C1C;font-size:12px;font-weight:600;cursor:pointer;padding:4px;">Remove</button>
            </div>

            <div id="coupon-entry" style="display:<?php echo !empty($cartSummary['coupon_code']) ? 'none' : 'block'; ?>;">
              <label style="display:block;font-size:12px;font-weight:700;color:var(--stone);margin-bottom:7px;">Have a discount code?</label>
              <div style="display:flex;gap:8px;">
                <input type="text" id="coupon-code-input" class="form-input" placeholder="Enter code"
                       autocomplete="off" autocapitalize="characters"
                       style="flex:1;min-width:0;text-transform:uppercase;font-size:14px;padding:11px 12px;">
                <button type="button" onclick="applyCoupon()" id="coupon-apply-btn" class="btn btn-dark"
                        style="flex-shrink:0;padding:11px 18px;font-size:13px;">Apply</button>
              </div>
              <p id="coupon-msg" style="font-size:12px;margin:8px 0 0;display:none;"></p>
            </div>
          </div>

          <!-- Total -->
          <div style="display:flex;justify-content:space-between;padding-top:16px;border-top:2px solid var(--black);margin-bottom:20px;">
            <span style="font-weight:700;font-size:15px;color:var(--black);">Total</span>
            <span id="co-total-display" style="font-family:'Cormorant',serif;font-size:24px;font-weight:700;color:var(--black);"><?php echo formatPrice($cartSummary['total']); ?></span>
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
/* ── Steps indicator ─────────────────────────────────────────────
   Spelled out in full this strip wants about 440px. A Galaxy S22 Ultra gives
   the page 412, so on a phone the first and last steps were being sliced off
   at the edges. It now shrinks in stages instead of overflowing. */
.co-steps-bar { background:var(--white); border-bottom:1px solid var(--cream-dark); padding:16px 0; }
.co-steps     { display:flex; align-items:center; justify-content:center; min-width:0; }
.co-step      { display:flex; align-items:center; gap:8px; padding:0 12px; min-width:0; }
.co-step-num  {
  width:28px; height:28px; border-radius:50%; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-size:12px; font-weight:700;
  background:var(--cream-dark); color:var(--stone-mid);
}
.co-step.is-done   .co-step-num { background:var(--gold);  color:#fff; }
.co-step.is-active .co-step-num { background:var(--black); color:#fff; }
.co-step-label { font-size:13px; font-weight:500; color:var(--stone-mid); white-space:nowrap; }
.co-step.is-active .co-step-label { font-weight:700; color:var(--black); }
.co-step-line  { width:40px; height:1px; background:var(--cream-dark); flex-shrink:0; }
.co-step-short { display:none; }

@media (max-width:620px) {
  .co-step      { padding:0 7px; gap:6px; }
  .co-step-line { width:18px; }
  .co-step-num  { width:25px; height:25px; font-size:11px; }
  .co-step-label{ font-size:12px; }
  /* Swap to the short wording: "Shipping" instead of "Shipping & Payment" */
  .co-step-long { display:none; }
  .co-step-short{ display:inline; }
}
@media (max-width:400px) {
  .co-steps-bar { padding:12px 0; }
  .co-step      { padding:0 5px; gap:5px; }
  .co-step-line { width:12px; }
  /* Last resort: keep the numbered circles and label only where the user is */
  .co-step:not(.is-active) .co-step-label { display:none; }
}

/* ── Layout ──────────────────────────────────────────────────────
   Two columns need roughly 700px of usable width before the summary card
   starts squeezing the form, so tablets in portrait get a single column with
   the summary underneath - but capped, so the fields don't sprawl across the
   full width of an iPad. */
@media(max-width:900px){
  #checkout-cols { grid-template-columns:1fr !important; max-width:640px; margin:0 auto; }
  #checkout-cols > div:last-child { position:static !important; }
}
@media(min-width:901px) and (max-width:1100px){
  /* Small laptops and landscape tablets: narrow the summary so the address
     fields keep a comfortable width. */
  #checkout-cols { grid-template-columns:1fr 300px !important; gap:24px !important; }
}
@media(max-width:480px){
  #checkout-cols .card { padding:18px !important; }
}
@media(max-width:380px){
  /* Two fields side by side stop being usable below this */
  #checkout-cols [style*="grid-template-columns:1fr 1fr"] { grid-template-columns:1fr !important; }
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
      if (!d.success) return;
      if (display) {
        display.style.color = d.is_free ? '#22C55E' : 'var(--black)';
        display.textContent = d.is_free ? 'FREE' : d.formatted;
        var row = display.closest('[style*="justify-content:space-between"]');
        if (row) {
          var lbl = row.querySelector('span:first-child');
          if (lbl) lbl.innerHTML = 'Shipping <span style="font-size:11px;">(' + d.state + ')</span>';
        }
      }
    })
    .catch(function() {});
}

// Auto-fire on page load if state already selected
(function() {
  var sel = document.getElementById('co-state-select');
  if (sel && sel.value) updateCheckoutShipping(sel.value);
})();

/* ── Discount codes ─────────────────────────────────────── */
function couponCurrentState() {
  var sel = document.getElementById('co-state-select');
  return sel && sel.value ? sel.value : '';
}

function couponShowMessage(text, isError) {
  var p = document.getElementById('coupon-msg');
  if (!p) return;
  p.textContent = text;
  p.style.color = isError ? '#B91C1C' : '#15803D';
  p.style.display = text ? 'block' : 'none';
}

function couponPaintTotals(t) {
  var disRow = document.getElementById('co-discount-row');
  var disVal = document.getElementById('co-discount-display');
  var disCode = document.getElementById('co-discount-code');
  var ship   = document.getElementById('co-shipping-display');
  var total  = document.getElementById('co-total-display');

  if (disRow) disRow.style.display = t.discount_raw > 0 ? 'flex' : 'none';
  if (disVal) disVal.textContent = '-' + t.discount;
  if (disCode) disCode.textContent = t.coupon_code || '';
  if (ship) {
    ship.textContent = t.shipping;
    ship.style.color = t.shipping_is_free ? '#22C55E' : 'var(--black)';
  }
  if (total) total.textContent = t.total;
}

function applyCoupon() {
  var input = document.getElementById('coupon-code-input');
  var btn   = document.getElementById('coupon-apply-btn');
  var code  = (input.value || '').trim().toUpperCase();
  if (!code) { couponShowMessage('Enter a code first.', true); return; }

  btn.disabled = true;
  btn.textContent = '...';
  couponShowMessage('', false);

  var body = new URLSearchParams();
  body.set('action', 'apply');
  body.set('code', code);
  body.set('state', couponCurrentState());

  fetch('api/apply-coupon.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
    body: body.toString()
  })
    .then(function(r){ return r.json(); })
    .then(function(d){
      btn.disabled = false;
      btn.textContent = 'Apply';
      if (!d.success) { couponShowMessage(d.message || 'That code did not work.', true); return; }

      couponPaintTotals(d.totals);
      document.getElementById('coupon-applied-code').textContent = d.code;
      document.getElementById('coupon-applied').style.display = 'flex';
      document.getElementById('coupon-entry').style.display = 'none';
      input.value = '';
    })
    .catch(function(){
      btn.disabled = false;
      btn.textContent = 'Apply';
      couponShowMessage('Could not check that code. Try again.', true);
    });
}

function removeCoupon() {
  var body = new URLSearchParams();
  body.set('action', 'remove');
  body.set('state', couponCurrentState());

  fetch('api/apply-coupon.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
    body: body.toString()
  })
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (!d.success) return;
      couponPaintTotals(d.totals);
      document.getElementById('coupon-applied').style.display = 'none';
      document.getElementById('coupon-entry').style.display = 'block';
      couponShowMessage('', false);
    });
}

/* Enter key applies the code instead of submitting the order */
(function(){
  var input = document.getElementById('coupon-code-input');
  if (input) {
    input.addEventListener('keydown', function(e){
      if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); }
    });
  }
})();
</script>

<?php require_once 'includes/footer.php'; ?>
