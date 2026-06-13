// Phelyz Store — Main JS
// All UI interactions — routes through showToast defined in header.php

/* ── Cart ─────────────────────────────────────────── */
function getSelectedColorFromPage() {
  var el = document.getElementById('selected-color');
  return el ? (el.value || '') : '';
}

function requireColorIfPresent() {
  // If the product page exposes color swatches, a colour must be picked.
  var hidden = document.getElementById('selected-color');
  if (!hidden) return true; // no color picker on this page
  if (!document.querySelector('.color-swatch-btn')) return true;
  if (!hidden.value) {
    showToast('Please pick a colour first', 'error');
    return false;
  }
  return true;
}

async function addToCart(productId, quantity, color) {
  quantity = quantity || 1;
  // If invoked from product detail page, enforce color choice
  if (color === undefined) {
    if (!requireColorIfPresent()) return;
    color = getSelectedColorFromPage();
  }
  var btn = event && event.target ? event.target.closest('button') : null;
  if (btn) btn.classList.add('btn-loading');
  try {
    var res  = await fetch('/api/add-to-cart.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ product_id: productId, quantity: quantity, selected_color: color || null })
    });
    var data = await res.json();
    if (data.success) {
      showToast(data.message || 'Added to cart!', 'success');
      updateCartBadge(data.cart_count);
    } else {
      showToast(data.message || 'Could not add to cart', 'error');
    }
  } catch(e) {
    showToast('Network error — please try again', 'error');
  }
  if (btn) btn.classList.remove('btn-loading');
}

async function addToCartWithQty(productId) {
  var qtyEl = document.getElementById('product-qty');
  var qty   = qtyEl ? parseInt(qtyEl.value) : 1;
  await addToCart(productId, qty);
}

async function buyNow(productId) {
  if (!requireColorIfPresent()) return;
  var qtyEl = document.getElementById('product-qty');
  var qty   = qtyEl ? parseInt(qtyEl.value) : 1;
  var color = getSelectedColorFromPage();
  try {
    var res  = await fetch('/api/add-to-cart.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ product_id: productId, quantity: qty, selected_color: color || null })
    });
    var data = await res.json();
    if (data.success) window.location.href = '/checkout.php';
    else showToast(data.message || 'Could not add to cart', 'error');
  } catch(e) { showToast('Network error', 'error'); }
}

/* ── Wishlist ──────────────────────────────────────── */
async function addToWishlist(productId, callback) {
  try {
    var res  = await fetch('/api/add-to-wishlist.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ product_id: productId, action: 'toggle' })
    });
    var data = await res.json();
    if (data.success) {
      showToast(data.message || 'Wishlist updated', 'success');
      if (callback) callback(data);
    } else {
      showToast(data.message || 'Sign in to use wishlist', 'info');
    }
  } catch(e) { showToast('Network error', 'error'); }
}

async function toggleWishlist(productId) {
  try {
    var res  = await fetch('/api/add-to-wishlist.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ product_id: productId, action: 'toggle' })
    });
    var data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      var btn  = document.getElementById('wishlist-btn');
      var icon = document.getElementById('wishlist-icon');
      var text = document.getElementById('wishlist-text');
      if (btn && icon && text) {
        var added = data.action === 'added';
        icon.setAttribute('fill', added ? 'currentColor' : 'none');
        btn.style.color = added ? '#EF4444' : '';
        text.textContent = added ? 'Saved to Wishlist' : 'Add to Wishlist';
      }
    } else { showToast(data.message || 'Sign in first', 'info'); }
  } catch(e) { showToast('Network error', 'error'); }
}

/* ── Cart badge ────────────────────────────────────── */
function updateCartBadge(count) {
  var badges = document.querySelectorAll('.nav-badge');
  badges.forEach(function(b){
    if (count > 0) { b.textContent = count; b.style.display = 'flex'; }
    else { b.style.display = 'none'; }
  });
  var cartLink = document.getElementById('cart-nav-link');
  if (cartLink) cartLink.setAttribute('aria-label', 'Cart (' + count + ' items)');
}

/* ── Product page qty ──────────────────────────────── */
function increaseQty() {
  var el  = document.getElementById('product-qty');
  var max = parseInt(el.max) || 99;
  if (parseInt(el.value) < max) el.value = parseInt(el.value) + 1;
}
function decreaseQty() {
  var el = document.getElementById('product-qty');
  if (parseInt(el.value) > 1) el.value = parseInt(el.value) - 1;
}

/* ── Cart page qty ─────────────────────────────────── */
function changeQty(btn, delta) {
  var wrapper  = btn.closest('.qty-stepper');
  var input    = wrapper ? wrapper.querySelector('.qty-input') : null;
  if (!input) return;
  var newVal   = Math.max(1, Math.min(parseInt(input.max)||99, parseInt(input.value) + delta));
  input.value  = newVal;
  var itemId   = input.getAttribute('data-item-id');
  if (itemId) updateQuantity(itemId, newVal);
}

async function updateQuantity(itemId, qty) {
  try {
    var res  = await fetch('/api/update-cart.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ cart_item_id: itemId, quantity: qty })
    });
    var data = await res.json();
    if (data.success) {
      updateCartBadge(data.cart_count);
      setTimeout(function(){ location.reload(); }, 300);
    } else { showToast(data.message || 'Update failed', 'error'); }
  } catch(e) { showToast('Network error', 'error'); }
}

async function removeFromCart(itemId) {
  try {
    var res  = await fetch('/api/update-cart.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ cart_item_id: itemId, quantity: 0 })
    });
    var data = await res.json();
    if (data.success) { updateCartBadge(data.cart_count); location.reload(); }
    else { showToast(data.message || 'Remove failed', 'error'); }
  } catch(e) { showToast('Network error', 'error'); }
}

async function clearCart() {
  if (!confirm('Remove all items from your cart?')) return;
  try {
    var res  = await fetch('/api/update-cart.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'clear' })
    });
    var data = await res.json();
    if (data.success) { updateCartBadge(0); location.reload(); }
    else { showToast(data.message || 'Failed to clear cart', 'error'); }
  } catch(e) { showToast('Network error', 'error'); }
}

/* ── Product page tabs ─────────────────────────────── */
function switchTab(tab) {
  ['description','reviews'].forEach(function(t) {
    var btn   = document.getElementById('tab-' + t);
    var panel = document.getElementById('panel-' + t);
    var active = t === tab;
    if (btn) {
      btn.style.color = active ? 'var(--gold)' : 'var(--stone-mid)';
      btn.style.borderBottomColor = active ? 'var(--gold)' : 'transparent';
    }
    if (panel) panel.style.display = active ? 'block' : 'none';
  });
}

/* ── Review star picker ────────────────────────────── */
function setRating(val) {
  document.getElementById('rating-input').value = val;
  document.querySelectorAll('.star-pick').forEach(function(s,i){
    s.setAttribute('fill', i < val ? '#FBBF24' : 'none');
    s.setAttribute('stroke', i < val ? '#FBBF24' : '#D4D4D4');
  });
}

/* ── Fallback notification (before header.php loads showToast) ── */
if (typeof showToast === 'undefined') {
  window.showToast = function(msg, type) {
    console.log('[Toast]', type, msg);
    var d = document.createElement('div');
    d.style.cssText = 'position:fixed;top:80px;right:16px;z-index:9999;padding:14px 18px;background:white;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,0.12);border-left:4px solid '+(type==='error'?'#EF4444':type==='info'?'#3B82F6':'#22C55E')+';font-size:14px;font-weight:500;min-width:260px;';
    d.textContent = msg;
    document.body.appendChild(d);
    setTimeout(function(){d.style.opacity='0';d.style.transition='opacity 0.3s';setTimeout(function(){d.remove();},300);},3000);
  };
}
