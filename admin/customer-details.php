<?php
$pageTitle = "Customer Details";
require_once 'includes/header.php';

$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$customerId) redirect('customers.php');

$db = getDB();
$customer = $db->fetchOne("SELECT * FROM users WHERE id = ? AND role = 'customer'", [$customerId]);
if (!$customer) redirect('customers.php');

$orders = getOrdersByUser($customerId);
$totalOrders = count($orders);
$totalSpent = $db->fetchOne(
    "SELECT SUM(total) as total FROM orders WHERE user_id = ? AND status != 'cancelled'",
    [$customerId]
)['total'] ?? 0;
$avgOrderValue = $totalOrders > 0 ? $totalSpent / $totalOrders : 0;
$wishlistCount = count(getWishlistItems());

$initials = strtoupper(
    substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)
);
$fullName = htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']);
?>

<!-- Top bar -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;
              gap:16px;margin-bottom:28px;flex-wrap:wrap;">
    <div>
      <nav class="breadcrumb" style="padding:0 0 6px;">
        <a href="dashboard.php">Dashboard</a>
        <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13">
          <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10
               7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1
               0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
        <a href="customers.php">Customers</a>
        <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13">
          <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10
               7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1
               0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
        <span style="color:var(--black);font-weight:600;"><?php echo $fullName; ?></span>
      </nav>
      <h1 class="admin-page-title">Customer Details</h1>
    </div>
    <a href="customers.php" class="btn btn-outline">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
           stroke-width="2" stroke="currentColor" width="15" height="15">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
      </svg>
      Back to Customers
    </a>
  </div>

  <!-- 2-column layout: 1fr | 2fr -->
  <div style="display:grid;grid-template-columns:1fr 2fr;gap:22px;align-items:start;"
       class="customer-detail-grid">

    <!-- LEFT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Profile Card -->
      <div class="card" style="overflow:hidden;">
        <!-- Dark header with avatar -->
        <div style="background:linear-gradient(135deg,var(--black),var(--stone));
                    padding:28px 24px;text-align:center;">
          <?php if (!empty($customer['profile_image'])): ?>
            <img src="../<?php echo htmlspecialchars(UPLOAD_URL . $customer['profile_image']); ?>"
                 alt="<?php echo $fullName; ?>"
                 style="width:80px;height:80px;border-radius:50%;object-fit:cover;
                        border:3px solid var(--gold);margin:0 auto 14px;">
          <?php else: ?>
            <div style="width:80px;height:80px;border-radius:50%;background:var(--gold);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Cormorant',serif;font-size:30px;font-weight:700;
                        color:white;margin:0 auto 14px;border:3px solid rgba(202,138,4,0.4);">
              <?php echo $initials; ?>
            </div>
          <?php endif; ?>
          <h2 style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;
                     color:white;letter-spacing:0.02em;"><?php echo $fullName; ?></h2>
          <p style="font-size:12px;color:rgba(255,255,255,0.5);margin-top:4px;">
            Customer #<?php echo $customer['id']; ?>
          </p>
          <div style="margin-top:10px;">
            <span class="status-badge <?php echo $customer['is_active'] ? 'status-delivered' : 'status-cancelled'; ?>">
              <?php echo $customer['is_active'] ? 'Active' : 'Inactive'; ?>
            </span>
          </div>
        </div>

        <!-- Info list -->
        <div style="padding:18px 20px;display:flex;flex-direction:column;gap:12px;">
          <div style="display:flex;align-items:flex-start;gap:10px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.8" stroke="var(--gold)" width="16" height="16"
                 style="flex-shrink:0;margin-top:1px;">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0
                       01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25
                       0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5
                       4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
            </svg>
            <div style="min-width:0;">
              <p style="font-size:10px;font-weight:700;letter-spacing:0.07em;
                        text-transform:uppercase;color:var(--stone-mid);margin-bottom:2px;">Email</p>
              <p style="font-size:13px;color:var(--black);word-break:break-all;">
                <?php echo htmlspecialchars($customer['email']); ?>
              </p>
            </div>
          </div>

          <div style="display:flex;align-items:flex-start;gap:10px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.8" stroke="var(--gold)" width="16" height="16"
                 style="flex-shrink:0;margin-top:1px;">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0
                       002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97
                       1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963
                       3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
            </svg>
            <div>
              <p style="font-size:10px;font-weight:700;letter-spacing:0.07em;
                        text-transform:uppercase;color:var(--stone-mid);margin-bottom:2px;">Phone</p>
              <p style="font-size:13px;color:var(--black);">
                <?php echo htmlspecialchars($customer['phone'] ?? 'Not provided'); ?>
              </p>
            </div>
          </div>

          <div style="display:flex;align-items:flex-start;gap:10px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.8" stroke="var(--gold)" width="16" height="16"
                 style="flex-shrink:0;margin-top:1px;">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0
                       012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25
                       2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25
                       2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
            <div>
              <p style="font-size:10px;font-weight:700;letter-spacing:0.07em;
                        text-transform:uppercase;color:var(--stone-mid);margin-bottom:2px;">Joined</p>
              <p style="font-size:13px;color:var(--black);">
                <?php echo formatDate($customer['created_at']); ?>
              </p>
            </div>
          </div>

          <?php if (!empty($customer['address'])): ?>
            <div style="display:flex;align-items:flex-start;gap:10px;">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                   stroke-width="1.8" stroke="var(--gold)" width="16" height="16"
                   style="flex-shrink:0;margin-top:1px;">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
              </svg>
              <div>
                <p style="font-size:10px;font-weight:700;letter-spacing:0.07em;
                          text-transform:uppercase;color:var(--stone-mid);margin-bottom:2px;">Address</p>
                <p style="font-size:13px;color:var(--black);line-height:1.55;">
                  <?php echo htmlspecialchars($customer['address']); ?><br>
                  <?php echo htmlspecialchars($customer['city'].', '.$customer['state'].' '.($customer['zip_code']??'')); ?><br>
                  <?php echo htmlspecialchars($customer['country'] ?? 'Nigeria'); ?>
                </p>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Send email CTA -->
        <div style="padding:0 20px 20px;">
          <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>"
             class="btn btn-gold btn-full">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="2" stroke="currentColor" width="15" height="15">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0
                       01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25
                       0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5
                       4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
            </svg>
            Send Email
          </a>
        </div>
      </div>

      <!-- Stats Row -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <!-- Total Orders -->
        <div class="card" style="padding:16px;text-align:center;">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(202,138,4,0.10);
                      display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.8" stroke="var(--gold)" width="18" height="18">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45
                       1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125
                       0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
            </svg>
          </div>
          <p style="font-family:'Cormorant',serif;font-size:24px;font-weight:700;
                    color:var(--black);line-height:1;"><?php echo $totalOrders; ?></p>
          <p style="font-size:10px;font-weight:700;text-transform:uppercase;
                    letter-spacing:0.07em;color:var(--stone-mid);margin-top:4px;">Orders</p>
        </div>

        <!-- Wishlist -->
        <div class="card" style="padding:16px;text-align:center;">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(239,68,68,0.08);
                      display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.8" stroke="#EF4444" width="18" height="18">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312
                       2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0
                       7.22 9 12 9 12s9-4.78 9-12z"/>
            </svg>
          </div>
          <p style="font-family:'Cormorant',serif;font-size:24px;font-weight:700;
                    color:var(--black);line-height:1;"><?php echo $wishlistCount; ?></p>
          <p style="font-size:10px;font-weight:700;text-transform:uppercase;
                    letter-spacing:0.07em;color:var(--stone-mid);margin-top:4px;">Wishlist</p>
        </div>

        <!-- Total Spent -->
        <div class="card" style="padding:16px;text-align:center;">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(34,197,94,0.08);
                      display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.8" stroke="#22C55E" width="18" height="18">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0
                       1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12
                       12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303
                       0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <p style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;
                    color:var(--black);line-height:1;"><?php echo formatPrice($totalSpent); ?></p>
          <p style="font-size:10px;font-weight:700;text-transform:uppercase;
                    letter-spacing:0.07em;color:var(--stone-mid);margin-top:4px;">Total Spent</p>
        </div>

        <!-- Avg Order -->
        <div class="card" style="padding:16px;text-align:center;">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(59,130,246,0.08);
                      display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.8" stroke="#3B82F6" width="18" height="18">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504
                       1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125
                       1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621
                       0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125
                       1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496
                       3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125
                       0 01-1.125-1.125V4.125z"/>
            </svg>
          </div>
          <p style="font-family:'Cormorant',serif;font-size:20px;font-weight:700;
                    color:var(--black);line-height:1;"><?php echo formatPrice($avgOrderValue); ?></p>
          <p style="font-size:10px;font-weight:700;text-transform:uppercase;
                    letter-spacing:0.07em;color:var(--stone-mid);margin-top:4px;">Avg Order</p>
        </div>
      </div>

    </div><!-- /LEFT -->

    <!-- RIGHT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Orders Table -->
      <div class="card" style="overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--cream-dark);
                    display:flex;align-items:center;justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:10px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.8" stroke="var(--gold)" width="18" height="18">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0
                       002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424
                       48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664
                       0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25
                       0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012
                       0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095
                       4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125
                       1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504
                       1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75
                       12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
            </svg>
            <h2 style="font-family:'Cormorant',serif;font-size:17px;font-weight:700;
                       color:var(--black);">Order History</h2>
          </div>
          <span style="font-size:12px;color:var(--stone-mid);">
            <?php echo $totalOrders; ?> order<?php echo $totalOrders!=1?'s':''; ?>
          </span>
        </div>

        <?php if (empty($orders)): ?>
          <div style="text-align:center;padding:48px 24px;">
            <div style="width:60px;height:60px;border-radius:50%;background:var(--cream-dark);
                        display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                   stroke-width="1.5" stroke="var(--stone-mid)" width="28" height="28">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45
                         1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125
                         0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
              </svg>
            </div>
            <p style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;
                      color:var(--black);margin-bottom:6px;">No orders yet</p>
            <p style="font-size:13px;color:var(--stone-mid);">
              This customer hasn&rsquo;t placed any orders.
            </p>
          </div>
        <?php else: ?>
          <div style="overflow-x:auto;">
            <table class="data-table">
              <thead>
                <tr>
                  <th style="padding-left:20px;">Order #</th>
                  <th>Date</th>
                  <th>Items</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th style="padding-right:20px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $order): ?>
                  <?php
                  $itemCount = $db->fetchOne(
                      "SELECT SUM(quantity) as total FROM order_items WHERE order_id = ?",
                      [$order['id']]
                  )['total'] ?? 0;
                  ?>
                  <tr>
                    <td style="padding-left:20px;">
                      <strong style="color:var(--black);">
                        <?php echo htmlspecialchars($order['order_number']); ?>
                      </strong>
                    </td>
                    <td style="color:var(--stone);">
                      <?php echo formatDate($order['created_at']); ?>
                    </td>
                    <td style="color:var(--stone);">
                      <?php echo $itemCount; ?> item<?php echo $itemCount!=1?'s':''; ?>
                    </td>
                    <td style="font-weight:600;font-family:'Cormorant',serif;font-size:16px;">
                      <?php echo formatPrice($order['total']); ?>
                    </td>
                    <td>
                      <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                      </span>
                    </td>
                    <td style="padding-right:20px;">
                      <a href="order-details.php?id=<?php echo $order['id']; ?>"
                         class="btn btn-outline btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="2" stroke="currentColor" width="13" height="13">
                          <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12
                                   4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577
                                   16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                          <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        View
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Recent Activity Timeline -->
      <?php if (!empty($orders)): ?>
        <div class="card" style="padding:20px 22px;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.8" stroke="var(--gold)" width="18" height="18">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h2 style="font-family:'Cormorant',serif;font-size:17px;font-weight:700;
                       color:var(--black);">Recent Activity</h2>
          </div>
          <div style="display:flex;flex-direction:column;gap:0;">
            <?php foreach (array_slice($orders, 0, 5) as $i => $order): ?>
              <div style="display:flex;gap:12px;<?php echo $i < min(4, count($orders)-1) ? '' : ''; ?>">
                <div style="display:flex;flex-direction:column;align-items:center;">
                  <div style="width:30px;height:30px;border-radius:50%;
                              background:rgba(202,138,4,0.10);border:2px solid rgba(202,138,4,0.25);
                              display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="2" stroke="var(--gold)" width="13" height="13">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45
                               1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125
                               0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
                    </svg>
                  </div>
                  <?php if ($i < min(4, count($orders)-1)): ?>
                    <div style="width:1px;flex:1;min-height:14px;background:var(--cream-dark);margin:4px 0;"></div>
                  <?php endif; ?>
                </div>
                <div style="padding-bottom:<?php echo $i < min(4, count($orders)-1) ? '14px' : '0'; ?>;flex:1;min-width:0;">
                  <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <p style="font-weight:600;font-size:13px;color:var(--black);">
                      Order <?php echo htmlspecialchars($order['order_number']); ?>
                    </p>
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                      <?php echo ucfirst($order['status']); ?>
                    </span>
                  </div>
                  <p style="font-size:12px;color:var(--stone-mid);margin-top:2px;">
                    <?php echo formatDate($order['created_at']); ?>
                    &mdash;
                    <strong style="color:var(--black);"><?php echo formatPrice($order['total']); ?></strong>
                  </p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div><!-- /RIGHT -->
  </div><!-- /grid -->

<style>
@media (max-width: 1024px) {
  .customer-detail-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
