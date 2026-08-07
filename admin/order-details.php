<?php
$pageTitle = "Order Details";
require_once 'includes/header.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$orderId) redirect('orders.php');

$order = getOrderById($orderId);
if (!$order) redirect('orders.php');

$orderItems = getOrderItems($orderId);
$db = getDB();
$customer = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$order['user_id']]);

// ── Parcel tracking ─────────────────────────────────────────────────────────
require_once __DIR__ . '/../includes/tracking.php';
$parcelMsg = '';
$parcelErr = '';

// Create a parcel for legacy orders that predate tracking
if (isset($_GET['create_parcel'])) {
    if (createParcelForOrder($orderId)) $parcelMsg = 'Parcel created.';
    else $parcelErr = 'Could not create parcel.';
}

// Record a status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parcel_update'])) {
    $pid       = (int)($_POST['parcel_id'] ?? 0);
    $newStatus = $_POST['parcel_status'] ?? '';
    $locLabel  = sanitize($_POST['location_label'] ?? '');
    $note      = sanitize($_POST['event_note'] ?? '');
    $lat       = ($_POST['lat'] ?? '') !== '' ? (float)$_POST['lat'] : null;
    $lng       = ($_POST['lng'] ?? '') !== '' ? (float)$_POST['lng'] : null;

    if ($pid > 0 && isset(parcelStatuses()[$newStatus])) {
        if (addParcelEvent($pid, $newStatus, $locLabel ?: null, $note ?: null, $lat, $lng)) {
            // Keep the order's own status roughly in step with the parcel
            $map = ['picked_up'=>'shipped','in_transit'=>'shipped','arrived_hub'=>'shipped',
                    'out_for_delivery'=>'shipped','delivered'=>'delivered'];
            if (isset($map[$newStatus]) && $order['status'] !== $map[$newStatus]) {
                $db->update('orders', ['status' => $map[$newStatus]], 'id = ?', [$orderId]);
                $order['status'] = $map[$newStatus];
                $currentStepRefresh = true;
            }
            // ETA can be adjusted at the same time
            if (!empty($_POST['eta_date'])) {
                $db->update('parcels', ['eta_date' => $_POST['eta_date']], 'id = ?', [$pid]);
            }
            $parcelMsg = 'Tracking updated - the customer can see this immediately.';
        } else {
            $parcelErr = 'Could not save the update.';
        }
    } else {
        $parcelErr = 'Pick a valid status.';
    }
}

$parcels = getParcelsByOrder($orderId);

$paymentMethods = [
    'cod'           => 'Cash on Delivery',
    'bank_transfer' => 'Bank Transfer',
    'paypal'        => 'PayPal',
    'card'          => 'Credit / Debit Card',
];

$statusSteps = [
    ['key' => 'pending',    'label' => 'Order Placed'],
    ['key' => 'processing', 'label' => 'Processing'],
    ['key' => 'shipped',    'label' => 'Shipped'],
    ['key' => 'delivered',  'label' => 'Delivered'],
];
$statusOrder = ['pending' => 0, 'processing' => 1, 'shipped' => 2, 'delivered' => 3, 'cancelled' => -1];
$currentStep = $statusOrder[$order['status']] ?? 0;
?>

<?php if ($parcelMsg): ?>
<div class="alert alert-success" style="margin-bottom:18px;"><?php echo htmlspecialchars($parcelMsg); ?></div>
<?php endif; ?>
<?php if ($parcelErr): ?>
<div class="alert alert-error" style="margin-bottom:18px;"><?php echo htmlspecialchars($parcelErr); ?></div>
<?php endif; ?>

<!-- ══════════ PARCEL TRACKING ══════════ -->
<div class="card" style="padding:24px;margin-bottom:22px;">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
    <h3 style="font-family:'Cormorant',serif;font-size:19px;font-weight:700;color:var(--black);margin:0;">Parcel &amp; Tracking</h3>
    <?php if (empty($parcels)): ?>
      <a href="?id=<?php echo $orderId; ?>&create_parcel=1" class="btn btn-gold btn-sm" style="font-size:12px;">+ Create parcel</a>
    <?php endif; ?>
  </div>

  <?php if (empty($parcels)): ?>
    <p style="font-size:13px;color:var(--stone-mid);margin:0;">
      No parcel yet for this order. New orders get one automatically - click “Create parcel” for older orders.
    </p>
  <?php else: ?>
    <?php foreach ($parcels as $p):
      $pm  = parcelStatusMeta($p['status']);
      $evs = getParcelEvents($p['id']); ?>
      <div style="border:1px solid var(--cream-dark);border-radius:11px;padding:18px;margin-bottom:14px;">
        <div class="parcel-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
          <div>
            <div style="font-size:10px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--stone-mid);">Tracking ID</div>
            <div style="font-size:17px;font-weight:700;color:var(--black);letter-spacing:0.03em;font-family:'Cormorant',serif;">
              <?php echo htmlspecialchars($p['tracking_id'] ?? ''); ?>
            </div>
            <div style="font-size:11.5px;color:var(--stone-mid);margin-top:3px;">
              Parcel <?php echo htmlspecialchars($p['parcel_number'] ?? ''); ?>
              &nbsp;·&nbsp; to <?php echo htmlspecialchars($p['dest_label'] ?: '-'); ?>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="background:<?php echo $pm['colour']; ?>1A;color:<?php echo $pm['colour']; ?>;padding:6px 14px;border-radius:99px;font-size:12px;font-weight:700;">
              <?php echo htmlspecialchars($pm['label'] ?? ''); ?>
            </span>
            <a href="../track.php?id=<?php echo urlencode($p['tracking_id'] ?? ''); ?>" target="_blank"
               style="font-size:12px;font-weight:600;color:var(--gold);text-decoration:none;">Customer view ↗</a>
          </div>
        </div>

        <!-- Update form -->
        <form method="POST" style="border-top:1px solid var(--cream-dark);padding-top:16px;">
          <input type="hidden" name="parcel_update" value="1">
          <input type="hidden" name="parcel_id" value="<?php echo (int)$p['id']; ?>">

          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">New status *</label>
              <select name="parcel_status" required class="form-input form-select">
                <?php foreach (parcelStatuses() as $key => $s): ?>
                  <option value="<?php echo $key; ?>" <?php echo $p['status'] === $key ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s['label'] ?? ''); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Where is it now?</label>
              <input type="text" name="location_label" class="form-input" placeholder="e.g. Aba sorting centre">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Revised ETA</label>
              <input type="date" name="eta_date" class="form-input" value="<?php echo htmlspecialchars($p['eta_date'] ?? ''); ?>">
            </div>
          </div>

          <div class="form-group" style="margin-top:14px;">
            <label class="form-label">Note for the customer <span style="color:var(--stone-mid);font-weight:400;">(optional)</span></label>
            <input type="text" name="event_note" class="form-input" placeholder="e.g. Rider will call on arrival">
          </div>

          <details style="margin-top:10px;">
            <summary style="font-size:12px;color:var(--stone-mid);cursor:pointer;">Set exact map coordinates (optional)</summary>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px;max-width:400px;">
              <input type="text" name="lat" class="form-input" placeholder="Latitude e.g. 5.0377">
              <input type="text" name="lng" class="form-input" placeholder="Longitude e.g. 7.9128">
            </div>
            <p class="form-hint">Leave blank and the map position follows the status automatically.</p>
          </details>

          <button type="submit" class="btn btn-gold btn-sm" style="margin-top:14px;">Post Update</button>
        </form>

        <!-- Recent events -->
        <?php if (!empty($evs)): ?>
          <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--cream-dark);">
            <div style="font-size:10px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--stone-mid);margin-bottom:10px;">
              History (<?php echo count($evs); ?>)
            </div>
            <?php foreach (array_slice(array_reverse($evs), 0, 6) as $ev):
              $em = parcelStatusMeta($ev['status']); ?>
              <div class="parcel-event" style="display:flex;align-items:flex-start;gap:9px;margin-bottom:9px;font-size:12.5px;">
                <span style="width:8px;height:8px;border-radius:50%;background:<?php echo $em['colour']; ?>;margin-top:5px;flex-shrink:0;"></span>
                <div style="flex:1;">
                  <strong style="color:var(--black);"><?php echo htmlspecialchars($em['label'] ?? ''); ?></strong>
                  <?php if ($ev['label']): ?><span style="color:var(--stone);"> - <?php echo htmlspecialchars($ev['label'] ?? ''); ?></span><?php endif; ?>
                  <?php if ($ev['note']): ?><div style="color:var(--stone-mid);font-size:11.5px;"><?php echo htmlspecialchars($ev['note'] ?? ''); ?></div><?php endif; ?>
                </div>
                <span class="parcel-event-time" style="color:var(--stone-mid);font-size:11px;white-space:nowrap;"><?php echo date('j M, g:ia', strtotime($ev['created_at'])); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

  <!-- Top bar: Breadcrumb + Actions -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;
              gap:16px;margin-bottom:28px;flex-wrap:wrap;">
    <!-- Breadcrumb -->
    <div>
      <nav class="breadcrumb" style="padding:0 0 6px;">
        <a href="dashboard.php">Dashboard</a>
        <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13">
          <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10
               7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1
               0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
        <a href="orders.php">Orders</a>
        <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13">
          <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10
               7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1
               0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
        <span style="color:var(--black);font-weight:600;">
          #<?php echo htmlspecialchars($order['order_number'] ?? ''); ?>
        </span>
      </nav>
      <h1 class="admin-page-title">
        Order #<?php echo htmlspecialchars($order['order_number'] ?? ''); ?>
      </h1>
    </div>

    <!-- Action buttons -->
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <a href="orders.php" class="btn btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="2" stroke="currentColor" width="15" height="15">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Back to Orders
      </a>
      <button onclick="window.print()" class="btn btn-dark">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="2" stroke="currentColor" width="15" height="15">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415
                   42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66
                   18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34
                   18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055
                   48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015
                   1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5
                   0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18
                   10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
        </svg>
        Print
      </button>
    </div>
  </div>

  <!-- Status update form -->
  <?php if (!in_array($order['status'], ['delivered', 'cancelled'])): ?>
    <div class="card status-update-card" style="padding:18px 22px;margin-bottom:24px;
                             display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
      <div style="display:flex;align-items:center;gap:8px;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="2" stroke="var(--gold)" width="18" height="18">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993
                   0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0
                   0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
        </svg>
        <span style="font-size:12px;font-weight:700;text-transform:uppercase;
                     letter-spacing:0.07em;color:var(--stone);">Update Status</span>
      </div>
      <div style="display:flex;align-items:center;gap:8px;flex:1;flex-wrap:wrap;">
        <span style="font-size:13px;color:var(--stone-mid);">Current:</span>
        <span class="status-badge status-<?php echo $order['status']; ?>">
          <?php echo ucfirst($order['status'] ?? ''); ?>
        </span>
        <svg class="status-row-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="2" stroke="var(--stone-mid)" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
        <form method="GET" action="update-order-status.php"
              style="display:flex;align-items:center;gap:8px;">
          <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
          <select name="status" class="form-input form-select"
                  style="padding:9px 36px 9px 12px;font-size:13px;width:auto;min-width:200px;">
            <option value="">Choose new status</option>
            <?php if ($order['status'] === 'pending'): ?>
              <option value="processing">Mark as Processing</option>
              <option value="shipped">Mark as Shipped</option>
              <option value="cancelled">Cancel Order</option>
            <?php elseif ($order['status'] === 'processing'): ?>
              <option value="shipped">Mark as Shipped</option>
              <option value="cancelled">Cancel Order</option>
            <?php elseif ($order['status'] === 'shipped'): ?>
              <option value="delivered">Mark as Delivered</option>
            <?php endif; ?>
          </select>
          <button type="submit" class="btn btn-gold btn-sm">Update</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- 2-column layout: 2fr | 1fr -->
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:22px;align-items:start;"
       class="order-detail-grid">

    <!-- LEFT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:22px;">

      <!-- Order Items Table -->
      <div class="card" style="overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--cream-dark);
                    display:flex;align-items:center;gap:10px;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke-width="1.8" stroke="var(--gold)" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45
                     1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0
                     015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
          </svg>
          <h2 style="font-family:'Cormorant',serif;font-size:17px;font-weight:700;
                     color:var(--black);">Order Items</h2>
          <span style="margin-left:auto;font-size:12px;color:var(--stone-mid);">
            <?php echo count($orderItems); ?> item<?php echo count($orderItems)!=1?'s':''; ?>
          </span>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th style="padding-left:20px;">Product</th>
                <th>Price</th>
                <th>Qty</th>
                <th style="text-align:right;padding-right:20px;">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orderItems as $item): ?>
                <tr>
                  <td style="padding-left:20px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                      <img src="<?php echo htmlspecialchars(productImageUrl($item['image'])); ?>"
                           alt="<?php echo htmlspecialchars($item['product_name'] ?? ''); ?>"
                           onerror="this.src='https://placehold.co/48x48/F5F5F4/78716C?text=+'"
                           style="width:48px;height:48px;object-fit:cover;
                                  border-radius:var(--radius-sm);background:var(--cream-dark);">
                      <div>
                        <div style="font-weight:600;font-size:13.5px;color:var(--black);">
                          <?php echo htmlspecialchars($item['product_name'] ?? ''); ?>
                        </div>
                        <?php if (!empty($item['variant'])): ?>
                          <div style="font-size:11px;color:var(--stone-mid);margin-top:2px;">
                            <?php echo htmlspecialchars($item['variant'] ?? ''); ?>
                          </div>
                        <?php endif; ?>
                        <?php if (!empty($item['selected_color'])): ?>
                          <div style="font-size:11px;color:var(--stone-mid);margin-top:2px;">
                            Colour: <?php echo htmlspecialchars($item['selected_color'] ?? ''); ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td style="color:var(--stone);">
                    <?php echo formatPrice($item['price_at_purchase']); ?>
                  </td>
                  <td>
                    <span style="display:inline-flex;align-items:center;justify-content:center;
                                 width:28px;height:28px;background:var(--cream-dark);
                                 border-radius:var(--radius-sm);font-size:13px;font-weight:700;">
                      <?php echo $item['quantity']; ?>
                    </span>
                  </td>
                  <td style="text-align:right;padding-right:20px;
                             font-weight:700;font-family:'Cormorant',serif;font-size:16px;">
                    <?php echo formatPrice($item['subtotal']); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Shipping Address -->
      <div class="card" style="padding:20px 22px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke-width="1.8" stroke="var(--gold)" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9
                     0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0
                     01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504
                     1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0
                     00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554
                     48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0
                     4.5v-4.5m0 0h-12"/>
          </svg>
          <h2 style="font-family:'Cormorant',serif;font-size:17px;font-weight:700;
                     color:var(--black);">Shipping Address</h2>
        </div>
        <div class="addr-2col" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div>
            <p style="font-size:11px;font-weight:700;letter-spacing:0.07em;
                      text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;">
              Recipient
            </p>
            <p style="font-weight:600;color:var(--black);">
              <?php echo htmlspecialchars($order['shipping_first_name'].' '.$order['shipping_last_name']); ?>
            </p>
          </div>
          <div>
            <p style="font-size:11px;font-weight:700;letter-spacing:0.07em;
                      text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;">
              Phone
            </p>
            <p style="color:var(--stone);">
              <?php echo htmlspecialchars($order['shipping_phone'] ?? ''); ?>
            </p>
          </div>
          <div style="grid-column:1/-1;">
            <p style="font-size:11px;font-weight:700;letter-spacing:0.07em;
                      text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;">
              Address
            </p>
            <p style="color:var(--stone);line-height:1.65;">
              <?php echo htmlspecialchars($order['shipping_address'] ?? ''); ?><br>
              <?php echo htmlspecialchars($order['shipping_city'].', '.$order['shipping_state'].' '.$order['shipping_zip']); ?><br>
              <?php echo htmlspecialchars($order['shipping_country'] ?? ''); ?>
            </p>
          </div>
        </div>
      </div>

      <!-- Billing Address (only if different) -->
      <div class="card" style="padding:20px 22px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke-width="1.8" stroke="var(--gold)" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75
                     3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5
                     4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
          </svg>
          <h2 style="font-family:'Cormorant',serif;font-size:17px;font-weight:700;
                     color:var(--black);">Billing Address</h2>
        </div>
        <div class="addr-2col" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div>
            <p style="font-size:11px;font-weight:700;letter-spacing:0.07em;
                      text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;">
              Name
            </p>
            <p style="font-weight:600;color:var(--black);">
              <?php echo htmlspecialchars($order['billing_first_name'].' '.$order['billing_last_name']); ?>
            </p>
          </div>
          <div>
            <p style="font-size:11px;font-weight:700;letter-spacing:0.07em;
                      text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;">
              Phone
            </p>
            <p style="color:var(--stone);">
              <?php echo htmlspecialchars($order['billing_phone'] ?? $order['shipping_phone'] ?? 'N/A'); ?>
            </p>
          </div>
          <div style="grid-column:1/-1;">
            <p style="font-size:11px;font-weight:700;letter-spacing:0.07em;
                      text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;">
              Address
            </p>
            <p style="color:var(--stone);line-height:1.65;">
              <?php echo htmlspecialchars($order['billing_address'] ?? $order['shipping_address'] ?? ''); ?><br>
              <?php echo htmlspecialchars(($order['billing_city'] ?? $order['shipping_city'] ?? '').', '.($order['billing_state'] ?? $order['shipping_state'] ?? '').' '.($order['billing_zip'] ?? '')); ?><br>
              <?php echo htmlspecialchars($order['billing_country'] ?? 'Nigeria'); ?>
            </p>
          </div>
        </div>
      </div>

      <!-- Order Notes -->
      <?php if (!empty($order['notes'])): ?>
        <div class="card" style="padding:20px 22px;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.8" stroke="var(--gold)" width="18" height="18">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707
                       3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12
                       21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233
                       2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394
                       0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
            </svg>
            <h2 style="font-family:'Cormorant',serif;font-size:17px;font-weight:700;
                       color:var(--black);">Order Notes</h2>
          </div>
          <p style="font-size:14px;color:var(--stone);line-height:1.7;
                    background:var(--cream-dark);padding:12px 16px;border-radius:var(--radius-sm);">
            <?php echo nl2br(htmlspecialchars($order['notes'] ?? '')); ?>
          </p>
        </div>
      <?php endif; ?>

      <!-- Customer Info -->
      <div class="card" style="padding:20px 22px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke-width="1.8" stroke="var(--gold)" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5
                     0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
          </svg>
          <h2 style="font-family:'Cormorant',serif;font-size:17px;font-weight:700;
                     color:var(--black);">Customer</h2>
        </div>
        <?php if ($customer): ?>
          <div class="customer-row" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="width:52px;height:52px;border-radius:50%;background:var(--gold);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Cormorant',serif;font-size:20px;font-weight:700;
                        color:white;flex-shrink:0;">
              <?php echo strtoupper(substr($customer['first_name'] ?? '',0,1).substr($customer['last_name'] ?? '',0,1)); ?>
            </div>
            <div style="flex:1;min-width:0;">
              <p style="font-weight:700;font-size:14px;color:var(--black);">
                <?php echo htmlspecialchars($customer['first_name'].' '.$customer['last_name']); ?>
              </p>
              <p style="font-size:13px;color:var(--stone-mid);margin-top:2px;">
                <?php echo htmlspecialchars($customer['email'] ?? ''); ?>
              </p>
              <?php if (!empty($customer['phone'])): ?>
                <p style="font-size:13px;color:var(--stone-mid);margin-top:2px;">
                  <?php echo htmlspecialchars($customer['phone'] ?? ''); ?>
                </p>
              <?php endif; ?>
            </div>
            <a href="customer-details.php?id=<?php echo $customer['id']; ?>"
               class="btn btn-outline btn-sm">
              View Profile
            </a>
          </div>
        <?php else: ?>
          <p style="font-size:13px;color:var(--stone-mid);">Guest Checkout - no account linked.</p>
        <?php endif; ?>
      </div>

    </div><!-- /LEFT -->

    <!-- RIGHT COLUMN (sticky) -->
    <div style="display:flex;flex-direction:column;gap:22px;position:sticky;top:24px;">

      <!-- Order Summary -->
      <div class="card" style="overflow:hidden;">
        <div style="padding:16px 20px;background:linear-gradient(135deg,var(--black),var(--stone));
                    color:white;">
          <p style="font-size:10px;font-weight:700;letter-spacing:0.12em;
                    text-transform:uppercase;opacity:0.5;margin-bottom:4px;">Order Summary</p>
          <p style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;letter-spacing:0.02em;">
            #<?php echo htmlspecialchars($order['order_number'] ?? ''); ?>
          </p>
          <p style="font-size:12px;opacity:0.55;margin-top:2px;">
            <?php echo formatDate($order['created_at']); ?>
          </p>
        </div>
        <div style="padding:16px 20px;">
          <!-- Line items -->
          <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;">
              <span style="color:var(--stone-mid);">Subtotal</span>
              <span><?php echo formatPrice($order['subtotal']); ?></span>
            </div>
            <?php if (!empty($order['tax']) && $order['tax'] > 0): ?>
              <div style="display:flex;justify-content:space-between;font-size:13px;">
                <span style="color:var(--stone-mid);">Tax</span>
                <span><?php echo formatPrice($order['tax']); ?></span>
              </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;font-size:13px;">
              <span style="color:var(--stone-mid);">Shipping</span>
              <span style="<?php echo $order['shipping']==0?'color:#22C55E;font-weight:600;':''; ?>">
                <?php echo $order['shipping']==0 ? 'FREE' : formatPrice($order['shipping']); ?>
              </span>
            </div>
          </div>
          <div style="height:1px;background:var(--cream-dark);margin-bottom:14px;"></div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:13px;font-weight:700;text-transform:uppercase;
                         letter-spacing:0.06em;">Total</span>
            <span style="font-family:'Cormorant',serif;font-size:22px;
                         font-weight:700;color:var(--gold);">
              <?php echo formatPrice($order['total']); ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Payment & Status -->
      <div class="card" style="padding:18px 20px;">
        <p style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;
                  color:var(--stone-mid);margin-bottom:14px;">Payment</p>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
            <span style="color:var(--stone-mid);">Method</span>
            <span style="font-weight:600;">
              <?php echo htmlspecialchars($paymentMethods[$order['payment_method']] ?? $order['payment_method']); ?>
            </span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
            <span style="color:var(--stone-mid);">Status</span>
            <span class="status-badge status-<?php echo $order['payment_status'] === 'paid' ? 'delivered' : 'pending'; ?>">
              <?php echo ucfirst($order['payment_status'] ?? ''); ?>
            </span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
            <span style="color:var(--stone-mid);">Order Status</span>
            <span class="status-badge status-<?php echo $order['status']; ?>">
              <?php echo ucfirst($order['status'] ?? ''); ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Order Timeline -->
      <div class="card" style="padding:18px 20px;">
        <p style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;
                  color:var(--stone-mid);margin-bottom:16px;">Order Timeline</p>
        <?php if ($order['status'] === 'cancelled'): ?>
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;border-radius:50%;background:#FEF2F2;
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                   stroke-width="2" stroke="#EF4444" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </div>
            <div>
              <p style="font-weight:700;font-size:13px;color:#991B1B;">Order Cancelled</p>
              <p style="font-size:11px;color:var(--stone-mid);"><?php echo formatDate($order['updated_at'] ?? $order['created_at']); ?></p>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($statusSteps as $i => $step): ?>
            <?php
            $done    = $currentStep >= $i;
            $current = $currentStep === $i;
            ?>
            <div style="display:flex;gap:12px;<?php echo $i < count($statusSteps)-1 ? 'margin-bottom:0;' : ''; ?>">
              <!-- connector + dot -->
              <div style="display:flex;flex-direction:column;align-items:center;">
                <div style="width:28px;height:28px;border-radius:50%;
                            background:<?php echo $done ? 'var(--gold)' : 'var(--cream-dark)'; ?>;
                            border:2px solid <?php echo $done ? 'var(--gold)' : 'var(--cream-dark)'; ?>;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;
                            transition:all 0.2s;">
                  <?php if ($done): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="2.5" stroke="white" width="13" height="13">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                  <?php endif; ?>
                </div>
                <?php if ($i < count($statusSteps)-1): ?>
                  <div style="width:2px;flex:1;min-height:20px;
                              background:<?php echo $currentStep > $i ? 'var(--gold)' : 'var(--cream-dark)'; ?>;
                              margin:3px 0;"></div>
                <?php endif; ?>
              </div>
              <!-- content -->
              <div style="padding-bottom:<?php echo $i < count($statusSteps)-1 ? '14px' : '0'; ?>;">
                <p style="font-weight:<?php echo $current ? '700' : '600'; ?>;
                          font-size:13px;
                          color:<?php echo $done ? 'var(--black)' : 'var(--stone-mid)'; ?>;">
                  <?php echo $step['label']; ?>
                </p>
                <?php if ($current && $step['key'] !== 'pending'): ?>
                  <p style="font-size:11px;color:var(--gold);font-weight:600;margin-top:1px;">In progress</p>
                <?php elseif ($step['key'] === 'pending' && $done): ?>
                  <p style="font-size:11px;color:var(--stone-mid);margin-top:1px;">
                    <?php echo formatDate($order['created_at']); ?>
                  </p>
                <?php elseif ($done && $step['key'] === 'delivered'): ?>
                  <p style="font-size:11px;color:#22C55E;font-weight:600;margin-top:1px;">Completed</p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div><!-- /RIGHT -->
  </div><!-- /grid -->

<style>
/* Grid items have the same min-width:auto default as flex items, so without
   this the column holding the order items table widens to fit the table and
   pushes everything else off the screen. */
.order-detail-grid > * { min-width: 0; }

@media (max-width: 1024px) {
  .order-detail-grid { grid-template-columns: 1fr !important; }
  .order-detail-grid > div:last-child { position: static !important; }
}

/* ── Phones and small tablets ──────────────────────────────────────────────
   Everything below the 1024px breakpoint was still laid out for a wide
   screen: two address columns side by side, the status form on a single
   line, a select pinned to 200px and dates told never to wrap. On a 412px
   phone those fight each other for room and overlap. */
@media (max-width: 640px) {

  /* Every hard two-column grid on the page stacks. Covers the shipping and
     billing addresses and the optional map coordinates. */
  .addr-2col,
  [style*="grid-template-columns:1fr 1fr"] {
    grid-template-columns: 1fr !important;
    max-width: none !important;
  }

  /* The status changer: label, badge, arrow, select and button were one
     unbroken row. Let it stack and give the controls the full width. */
  [style*="min-width:200px"] {
    min-width: 0 !important;
    width: 100% !important;
  }
  form[action="update-order-status.php"] {
    display: flex !important;
    flex-wrap: wrap;
    width: 100%;
    gap: 10px !important;
  }
  form[action="update-order-status.php"] .btn { width: 100%; }

  /* The chevron between "Current: <badge>" and the select points sideways
     across a layout that is now vertical. */
  .status-row-arrow { display: none !important; }

  /* Timestamps in the parcel history were nowrap, so a long status label had
     nowhere to break and shoved the date past the edge. Put it on its own
     line instead. */
  .parcel-event { flex-wrap: wrap; }
  .parcel-event-time {
    white-space: normal !important;
    width: 100%;
    padding-left: 17px;   /* clears the coloured dot */
    margin-top: 2px;
  }

  /* Tracking id and its status pill: let them sit one above the other rather
     than squeezing onto one line. */
  .parcel-head { flex-direction: column; gap: 12px !important; }

  /* Give the cards their space back. */
  .card { padding: 16px !important; }
  .order-detail-grid { gap: 16px !important; }

  /* The items table keeps a readable width and scrolls sideways inside its own
     box, which only works now the columns above are allowed to shrink. */
  .data-table { min-width: 460px; }

  /* "View Profile" sat at the end of a row with the customer's name and email
     and was the first thing to be clipped off the right edge. */
  .customer-row { flex-direction: column; align-items: flex-start !important; }
  .customer-row .btn { width: 100%; justify-content: center; }
}

@media (max-width: 400px) {
  /* Below this the auto-fit tracks still try for two columns. */
  [style*="minmax(180px,1fr)"] { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
