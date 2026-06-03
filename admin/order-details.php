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
          #<?php echo htmlspecialchars($order['order_number']); ?>
        </span>
      </nav>
      <h1 class="admin-page-title">
        Order #<?php echo htmlspecialchars($order['order_number']); ?>
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
          <?php echo ucfirst($order['status']); ?>
        </span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="2" stroke="var(--stone-mid)" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
        <form method="GET" action="update-order-status.php"
              style="display:flex;align-items:center;gap:8px;">
          <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
          <select name="status" class="form-input form-select"
                  style="padding:9px 36px 9px 12px;font-size:13px;width:auto;min-width:200px;">
            <option value="">— Choose new status —</option>
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
                      <img src="../<?php echo htmlspecialchars($item['image']); ?>"
                           alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                           onerror="this.src='https://placehold.co/48x48/F5F5F4/78716C?text=+'"
                           style="width:48px;height:48px;object-fit:cover;
                                  border-radius:var(--radius-sm);background:var(--cream-dark);">
                      <div>
                        <div style="font-weight:600;font-size:13.5px;color:var(--black);">
                          <?php echo htmlspecialchars($item['product_name']); ?>
                        </div>
                        <?php if (!empty($item['variant'])): ?>
                          <div style="font-size:11px;color:var(--stone-mid);margin-top:2px;">
                            <?php echo htmlspecialchars($item['variant']); ?>
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
              <?php echo htmlspecialchars($order['shipping_phone']); ?>
            </p>
          </div>
          <div style="grid-column:1/-1;">
            <p style="font-size:11px;font-weight:700;letter-spacing:0.07em;
                      text-transform:uppercase;color:var(--stone-mid);margin-bottom:4px;">
              Address
            </p>
            <p style="color:var(--stone);line-height:1.65;">
              <?php echo htmlspecialchars($order['shipping_address']); ?><br>
              <?php echo htmlspecialchars($order['shipping_city'].', '.$order['shipping_state'].' '.$order['shipping_zip']); ?><br>
              <?php echo htmlspecialchars($order['shipping_country']); ?>
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
            <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
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
          <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="width:52px;height:52px;border-radius:50%;background:var(--gold);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Cormorant',serif;font-size:20px;font-weight:700;
                        color:white;flex-shrink:0;">
              <?php echo strtoupper(substr($customer['first_name'],0,1).substr($customer['last_name'],0,1)); ?>
            </div>
            <div style="flex:1;min-width:0;">
              <p style="font-weight:700;font-size:14px;color:var(--black);">
                <?php echo htmlspecialchars($customer['first_name'].' '.$customer['last_name']); ?>
              </p>
              <p style="font-size:13px;color:var(--stone-mid);margin-top:2px;">
                <?php echo htmlspecialchars($customer['email']); ?>
              </p>
              <?php if (!empty($customer['phone'])): ?>
                <p style="font-size:13px;color:var(--stone-mid);margin-top:2px;">
                  <?php echo htmlspecialchars($customer['phone']); ?>
                </p>
              <?php endif; ?>
            </div>
            <a href="customer-details.php?id=<?php echo $customer['id']; ?>"
               class="btn btn-outline btn-sm">
              View Profile
            </a>
          </div>
        <?php else: ?>
          <p style="font-size:13px;color:var(--stone-mid);">Guest Checkout — no account linked.</p>
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
            #<?php echo htmlspecialchars($order['order_number']); ?>
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
              <?php echo ucfirst($order['payment_status']); ?>
            </span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
            <span style="color:var(--stone-mid);">Order Status</span>
            <span class="status-badge status-<?php echo $order['status']; ?>">
              <?php echo ucfirst($order['status']); ?>
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
@media (max-width: 1024px) {
  .order-detail-grid { grid-template-columns: 1fr !important; }
  .order-detail-grid > div:last-child { position: static !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
