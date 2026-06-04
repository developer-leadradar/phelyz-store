# Phelyz QA Progress
Generated: 2026-06-04 22:00

## Status: ALL PHASES COMPLETE ✅

## Environment State
- Last logged in as: customer@phelyz.com (tested logout successfully)
- Last deployment: c5058c9 (admin security fix)
- Any env vars added/changed: none
- Any Supabase schema changes: none

## All Issues Found & Fixed
| # | Page | Severity | Symptom | Root Cause | Fix Applied | File | Commit | Status |
|---|------|----------|---------|------------|-------------|------|--------|--------|
| 1 | All pages (header) | High | Cart badge didn't update after Add to Cart | nav-badge span only rendered by PHP when count>0; JS updateCartBadge couldn't find it | Always render span, hide via display:none when 0; also update aria-label | includes/header.php, assets/js/main.js | f3aa25c | Fixed |
| 2 | All pages (footer) | High | cart.js loaded globally with broken /phelyz-store/ paths, dead code | Old dev file included in footer.php | Removed script tag from footer.php | includes/footer.php | f3aa25c | Fixed |
| 3 | cart.php | High | showToast override: cart used Bootstrap-class alerts instead of site toast | Inline script in cart.php redefined showToast after header.php's good one | Removed entire inline script block (main.js handles all) | cart.php | f3aa25c | Fixed |
| 4 | shop.php | High | Sort by Price / Rating had no effect | shop.php sends price_asc/price_desc/rating; functions.php switch expected price_low/price_high/popular | Added both key names to each case | includes/functions.php | fc373ca | Fixed |
| 5 | product.php | Medium | Page title shows "Phelyz Store" instead of product name | header.php included on line 2 before $pageTitle set on line 18 | Moved product data fetch + defines before header include | product.php | e05c36a | Fixed |
| 6 | product.php | High | showToast/addToCart/wishlist/tabs inline JS overrode main.js; wrong cart badge ID | Duplicate inline JS for functions already in main.js | Removed all inline duplicates; submitReview fixed to use root-relative path | product.php | e05c36a | Fixed |
| 7 | api/add-to-wishlist.php | Medium | Wishlist toggle always added, never removed; UI state never updated | API didn't handle action=toggle or return data.action field | Added toggle resolution via isInWishlist(); return action:'added'/'removed' | api/add-to-wishlist.php | e05c36a | Fixed |
| 8 | customer-profile, customer-addresses, customer-wishlist, order-details | Critical | Pages completely blank (200 but 0 bytes) | UTF-8 BOM in 4 files caused output before ob_start() → broke session_start() → requireLogin() redirected → ob_end_clean() discarded all output | Stripped BOM with sed | 4 customer PHP files | 8ef58a1 | Fixed |
| 9 | includes/functions.php | High | Admin accounts could access customer dashboard (/customer-dashboard.php showed admin data) | requireLogin() only checked isLoggedIn(), not isAdmin() | Added isAdmin() check — redirects to admin/index.php | includes/functions.php | c5058c9 | Fixed |

## Outstanding Issues (Not Fixed — Low Priority)
- Tailwind CDN used in production (should be compiled) — affects load speed, not functionality
- Social media links in footer are # placeholders — expected until store has real social profiles
- Order ORD-2026-6842 has 0 items (placed before checkout fix was applied) — old data, not a code bug
- 404 page has empty `<title>` tag — cosmetic only

## All Fixes Committed
| Commit | Description |
|--------|-------------|
| f3aa25c | Fix: cart badge not updating, remove dead cart.js, fix showToast override on cart page |
| fc373ca | Fix: shop sort options not working (price_asc/price_desc/rating key mismatch) |
| e05c36a | Fix: product page title missing, bad inline JS, wishlist toggle API |
| 8ef58a1 | Fix: UTF-8 BOM in 4 PHP files (customer-profile, addresses, wishlist, order-details) |
| c5058c9 | Fix: admin can access customer dashboard — requireLogin now redirects admins |

## Phase Results Summary
### Phase 1 — Public Pages ✅ ALL PASS
- 1.1 Homepage — hero CTA, guest Add to Cart, guest wishlist prompt, badge updates
- 1.2 Shop — category filter, sort (all 4 options), search, empty state, pagination
- 1.3 Product detail — title (fixed), qty stepper, Add to Cart, Buy Now, tabs, related products
- 1.4 Cart — qty update, remove item, clear cart, empty state, Proceed to Checkout
- 1.5 Search — same engine as shop, verified working
- 1.6 Static pages — about, faq (15 Q&As), contact (form submits, success shows)
- 1.7 Auth — login errors, admin-on-customer-login blocked, register validation (4 cases), forgot-password

### Phase 2 — Customer Dashboard ✅ ALL PASS
- 2.1 Dashboard — hub layout, all quick links functional
- 2.2 Order History — filter tabs, empty state, 3 orders now showing
- 2.3 Order Details — items, address, status, totals all correct
- 2.4 Addresses — empty state with Add Address prompt
- 2.5 Profile — name/phone pre-filled, password change section present
- 2.6 Wishlist — items shown, Add to Cart from wishlist works
- 2.7 Checkout — address form, order summary, Place Order creates order, cart cleared after
- 2.8 Logout — redirects to homepage, dashboard redirects to login afterward

### Phase 3 — Admin Dashboard ✅ ALL PASS
- 3.1 Dashboard — real revenue/orders/customers/products stats, recent orders, low stock alerts
- 3.2 Products — 15 products with filter tabs, Edit/Delete actions
- 3.3 Add Product — required field validation, image upload, 8 categories
- 3.4 Edit Product — fields pre-filled correctly
- 3.5 Orders — 2 orders listed, filter tabs, search present
- 3.6 Order Management — full status chain: Pending→Processing→Shipped→Delivered ✅
- 3.7 Customers — 4 customers with status, email, join date, View link
- 3.8 Customer Details — loads correctly
- 3.9 Categories — Rings, Necklaces etc. with Active status
- 3.10 Reports — real revenue data, order breakdown, date filter
- 3.11 Settings — all sections pre-populated (store name, email, shipping, tax, payment)
- 3.12 Security:
  - Admin panel without login → redirects to admin/login.php ✅
  - Admin panel with customer account → redirects (opaqueredirect) ✅
  - Customer dashboard with admin account → now redirects to admin/index.php ✅ (fixed)
  - Customer pages after logout → redirect to login.php ✅

### Phase 4 — Cross-Cutting ✅ ALL PASS
- 4.1 Mobile layout — hamburger, drawer, media queries, Tailwind responsive classes all present
- 4.2 Cart persistence — guest cart merges on login ✅
- 4.3 Header/Nav — cart badge accurate, login state correct, logout works from any page
- 4.4 Error handling — nonexistent product redirects to shop ✅; 404 page shows gracefully ✅

## Known Broken (Per Brief — Email Delivery)
- Welcome email after registration — UI shows success, email won't arrive (Resend domain unverified)
- Forgot password reset email — UI shows success, email won't arrive
- Contact form email notification — UI shows success, email won't arrive

## Cross-Check: Admin Delivered → Customer Can Review ✅
Order ORD-2026-2450 was marked Delivered in admin. Customer orders page immediately showed "RATE YOUR ITEMS" button for that order. Review flow is correctly gated on delivery status.

## Notes For Future Reference
1. **BOM root cause**: 4 PHP files had UTF-8 BOM (byte order mark). BOM outputs 3 bytes before ob_start() → breaks session_start() ("headers already sent") → session empty → requireLogin() redirects → ob_end_clean() discards all buffered output → blank page.

2. **Inline JS pattern to watch**: Any PHP page that adds inline `<script>` blocks with showToast/cart/wishlist functions will override header.php's good implementations. main.js uses `if (typeof showToast === 'undefined')` as a guard — if inline script runs first, the guard always fails.

3. **Sort key naming**: functions.php getAllProducts() switch cases must match the values sent by shop.php select options. Currently both old names (price_low) and new names (price_asc) are handled.

4. **Session on Vercel**: Uses PgSessionHandler (PostgreSQL Supabase). JavaScript `document.cookie` clearing does NOT clear PHP httpOnly session cookies — use /logout.php to properly end sessions.

5. **Admin vs Customer accounts**: Admin accounts use `requireAdmin()` guard; customer pages use `requireLogin()` which now also blocks admins. The two account types are fully segregated.

6. **Deployment command**: `vercel deploy --prod` from `C:\xampp\htdocs\phelyz-store` (~30s build)
