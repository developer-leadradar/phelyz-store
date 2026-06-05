<?php
$pageTitle = "Reports & Analytics";
require_once 'includes/header.php';

$db = getDB();

// Date range filter
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Revenue stats
$revenueData = $db->fetchOne(
    "SELECT
        SUM(total) as total_revenue,
        AVG(total) as avg_order_value,
        COUNT(*) as total_orders
     FROM orders
     WHERE status != 'cancelled'
     AND created_at BETWEEN ? AND ?",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
);

$totalRevenue = $revenueData['total_revenue'] ?? 0;
$avgOrderValue = $revenueData['avg_order_value'] ?? 0;
$totalOrders = $revenueData['total_orders'] ?? 0;

// Order statistics
$orderStats = [
    'pending'    => $db->fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'pending' AND created_at BETWEEN ? AND ?",    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['total'],
    'processing' => $db->fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'processing' AND created_at BETWEEN ? AND ?", [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['total'],
    'shipped'    => $db->fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'shipped' AND created_at BETWEEN ? AND ?",    [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['total'],
    'delivered'  => $db->fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'delivered' AND created_at BETWEEN ? AND ?",  [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['total'],
    'cancelled'  => $db->fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'cancelled' AND created_at BETWEEN ? AND ?",  [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['total']
];

// Top selling products
$topProducts = $db->fetchAll(
    "SELECT
        p.id, p.name, p.price, p.image,
        c.name as category_name,
        SUM(oi.quantity) as units_sold,
        SUM(oi.subtotal) as revenue
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     JOIN categories c ON p.category_id = c.id
     JOIN orders o ON oi.order_id = o.id
     WHERE o.status != 'cancelled'
     AND o.created_at BETWEEN ? AND ?
     GROUP BY p.id
     ORDER BY units_sold DESC
     LIMIT 10",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
);

// Revenue by category
$categoryRevenue = $db->fetchAll(
    "SELECT
        c.name as category_name,
        SUM(oi.subtotal) as revenue,
        SUM(oi.quantity) as units_sold
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     JOIN categories c ON p.category_id = c.id
     JOIN orders o ON oi.order_id = o.id
     WHERE o.status != 'cancelled'
     AND o.created_at BETWEEN ? AND ?
     GROUP BY c.id
     ORDER BY revenue DESC",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
);

// Daily revenue (last 30 days)
$dailyRevenue = $db->fetchAll(
    "SELECT
        DATE(created_at) as date,
        SUM(total) as revenue,
        COUNT(*) as orders
     FROM orders
     WHERE status != 'cancelled'
     AND created_at >= CURRENT_DATE - INTERVAL '30 days'
     GROUP BY DATE(created_at)
     ORDER BY date ASC"
);

// Customer stats
$newCustomers = $db->fetchOne(
    "SELECT COUNT(*) as total FROM users WHERE role = 'customer' AND created_at BETWEEN ? AND ?",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
)['total'];

// Top customers
$topCustomers = $db->fetchAll(
    "SELECT
        u.id, u.first_name, u.last_name, u.email,
        COUNT(o.id) as order_count,
        SUM(o.total) as total_spent
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.status != 'cancelled'
     AND o.created_at BETWEEN ? AND ?
     GROUP BY u.id
     ORDER BY total_spent DESC
     LIMIT 10",
    [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
);
?>

<!-- Page header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
    <div>
        <div style="font-size:11px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:var(--gold);margin-bottom:4px;">Analytics</div>
        <h2 style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:var(--black);letter-spacing:-0.02em;margin:0;">Sales Reports</h2>
    </div>
    <button onclick="window.print()" class="btn btn-outline" style="gap:6px;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:16px;height:16px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
        </svg>
        Print Report
    </button>
</div>

<!-- ── Date Filter Bar ── -->
<div class="card" style="padding:20px;margin-bottom:24px;">
    <form method="GET" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;justify-content:center;">
        <div style="display:flex;align-items:center;gap:8px;">
            <label class="form-label" for="rpt_start" style="margin-bottom:0;white-space:nowrap;font-size:12px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--stone-mid);">From</label>
            <input type="date" id="rpt_start" name="start_date" class="form-input" style="margin-bottom:0;"
                   value="<?php echo htmlspecialchars($startDate); ?>">
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <label class="form-label" for="rpt_end" style="margin-bottom:0;white-space:nowrap;font-size:12px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--stone-mid);">To</label>
            <input type="date" id="rpt_end" name="end_date" class="form-input" style="margin-bottom:0;"
                   value="<?php echo htmlspecialchars($endDate); ?>">
        </div>
        <button type="submit" class="btn btn-gold" style="gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:15px;height:15px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/>
            </svg>
            Filter
        </button>
        <a href="reports.php" class="btn btn-outline" style="gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:15px;height:15px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
            Reset
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline" style="gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:15px;height:15px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
            </svg>
            Print
        </button>
    </form>
    <p style="font-size:12px;color:var(--stone-mid);margin:12px 0 0;font-weight:500;text-align:center;">
        Report period: <strong style="color:var(--black);"><?php echo date('M d, Y', strtotime($startDate)); ?></strong>
        &mdash;
        <strong style="color:var(--black);"><?php echo date('M d, Y', strtotime($endDate)); ?></strong>
    </p>
</div>

<!-- ── 4 KPI Stat Cards ── -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;" class="admin-kpi-grid">

    <!-- Total Revenue -->
    <div class="stat-card">
        <div class="stat-icon-box" style="background:rgba(202,138,4,0.12);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#CA8A04" style="width:22px;height:22px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="stat-number"><?php echo formatPrice($totalRevenue); ?></div>
        <div class="stat-label">Total Revenue</div>
    </div>

    <!-- Total Orders -->
    <div class="stat-card">
        <div class="stat-icon-box" style="background:rgba(59,130,246,0.12);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#3B82F6" style="width:22px;height:22px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
            </svg>
        </div>
        <div class="stat-number"><?php echo number_format($totalOrders); ?></div>
        <div class="stat-label">Total Orders</div>
    </div>

    <!-- New Customers -->
    <div class="stat-card">
        <div class="stat-icon-box" style="background:rgba(34,197,94,0.12);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#22C55E" style="width:22px;height:22px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
            </svg>
        </div>
        <div class="stat-number"><?php echo number_format($newCustomers); ?></div>
        <div class="stat-label">New Customers</div>
    </div>

    <!-- Avg Order Value -->
    <div class="stat-card">
        <div class="stat-icon-box" style="background:rgba(168,85,247,0.12);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#A855F7" style="width:22px;height:22px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
            </svg>
        </div>
        <div class="stat-number"><?php echo formatPrice($avgOrderValue); ?></div>
        <div class="stat-label">Avg Order Value</div>
    </div>
</div>

<!-- ── Order Status Breakdown ── -->
<div class="card" style="padding:24px;margin-bottom:24px;">
    <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);margin:0 0 20px;">Order Status Breakdown</h3>
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;" class="order-status-bk">
        <?php
        $statusDefs = [
            'pending'    => ['label'=>'Pending',    'bg'=>'#FFFBEB','color'=>'#92400E','dot'=>'#F59E0B'],
            'processing' => ['label'=>'Processing', 'bg'=>'#EFF6FF','color'=>'#1E40AF','dot'=>'#3B82F6'],
            'shipped'    => ['label'=>'Shipped',    'bg'=>'#F0FDF4','color'=>'#166534','dot'=>'#22C55E'],
            'delivered'  => ['label'=>'Delivered',  'bg'=>'#ECFDF5','color'=>'#065F46','dot'=>'#10B981'],
            'cancelled'  => ['label'=>'Cancelled',  'bg'=>'#FEF2F2','color'=>'#991B1B','dot'=>'#EF4444'],
        ];
        foreach ($statusDefs as $key => $def):
        ?>
        <div style="background:<?php echo $def['bg']; ?>;border-radius:10px;padding:18px 16px;text-align:center;">
            <div style="font-family:'Cormorant',serif;font-size:28px;font-weight:700;color:<?php echo $def['color']; ?>;line-height:1;margin-bottom:6px;"><?php echo $orderStats[$key]; ?></div>
            <div style="display:flex;align-items:center;justify-content:center;gap:5px;">
                <span style="width:7px;height:7px;border-radius:50%;background:<?php echo $def['dot']; ?>;flex-shrink:0;"></span>
                <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:<?php echo $def['color']; ?>;"><?php echo $def['label']; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── Top Products + Category Revenue (2-col) ── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;" class="admin-content-grid">

    <!-- Top Selling Products -->
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);margin:0 0 20px;">Top Selling Products</h3>
        <?php if (empty($topProducts)): ?>
            <div style="text-align:center;padding:40px 20px;color:var(--stone-mid);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:40px;height:40px;margin:0 auto 10px;opacity:0.4;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                </svg>
                <p style="font-size:13px;margin:0;">No sales data for this period</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th style="text-align:center;">Units</th>
                            <th style="text-align:right;">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $prod): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <img src="../<?php echo htmlspecialchars($prod['image']); ?>"
                                         alt="<?php echo htmlspecialchars($prod['name']); ?>"
                                         style="width:36px;height:36px;object-fit:cover;border-radius:6px;flex-shrink:0;border:1px solid var(--cream-dark);">
                                    <span style="font-weight:600;font-size:13px;color:var(--black);"><?php echo htmlspecialchars($prod['name']); ?></span>
                                </div>
                            </td>
                            <td style="color:var(--stone-mid);font-size:12px;"><?php echo htmlspecialchars($prod['category_name']); ?></td>
                            <td style="text-align:center;">
                                <span style="background:rgba(202,138,4,0.10);color:var(--gold);font-weight:700;font-size:12px;padding:3px 10px;border-radius:99px;"><?php echo $prod['units_sold']; ?></span>
                            </td>
                            <td style="text-align:right;font-weight:700;font-size:13px;color:var(--black);"><?php echo formatPrice($prod['revenue']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Revenue by Category -->
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);margin:0 0 20px;">Revenue by Category</h3>
        <?php if (empty($categoryRevenue)): ?>
            <div style="text-align:center;padding:40px 20px;color:var(--stone-mid);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:40px;height:40px;margin:0 auto 10px;opacity:0.4;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
                <p style="font-size:13px;margin:0;">No data available for this period</p>
            </div>
        <?php else:
            $maxRevenue = max(array_column($categoryRevenue, 'revenue')) ?: 1;
        ?>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <?php foreach ($categoryRevenue as $cat):
                    $pct = round(($cat['revenue'] / $maxRevenue) * 100);
                ?>
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <div>
                            <span style="font-size:13px;font-weight:600;color:var(--black);"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                            <span style="font-size:11px;color:var(--stone-mid);margin-left:6px;"><?php echo $cat['units_sold']; ?> units</span>
                        </div>
                        <span style="font-size:13px;font-weight:700;color:var(--black);"><?php echo formatPrice($cat['revenue']); ?></span>
                    </div>
                    <div style="height:6px;background:var(--cream-dark);border-radius:99px;overflow:hidden;">
                        <div style="height:100%;width:<?php echo $pct; ?>%;background:linear-gradient(90deg,#CA8A04,#D97706);border-radius:99px;transition:width 600ms ease;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Top Customers Table ── -->
<div class="card" style="padding:24px;margin-bottom:24px;">
    <h3 style="font-family:'Cormorant',serif;font-size:18px;font-weight:700;color:var(--black);margin:0 0 20px;">Top Customers</h3>
    <?php if (empty($topCustomers)): ?>
        <div style="text-align:center;padding:40px 20px;color:var(--stone-mid);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:40px;height:40px;margin:0 auto 10px;opacity:0.4;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
            <p style="font-size:13px;margin:0;">No customer data for this period</p>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th style="text-align:center;">Orders</th>
                        <th style="text-align:right;">Total Spent</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topCustomers as $customer): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;border-radius:50%;background:rgba(202,138,4,0.12);display:flex;align-items:center;justify-content:center;font-family:'Cormorant',serif;font-size:15px;font-weight:700;color:var(--gold);flex-shrink:0;">
                                    <?php echo strtoupper(substr($customer['first_name'],0,1)); ?>
                                </div>
                                <span style="font-weight:600;color:var(--black);"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></span>
                            </div>
                        </td>
                        <td style="color:var(--stone-mid);font-size:13px;"><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td style="text-align:center;">
                            <span style="background:rgba(59,130,246,0.10);color:#1E40AF;font-weight:700;font-size:12px;padding:3px 10px;border-radius:99px;"><?php echo $customer['order_count']; ?></span>
                        </td>
                        <td style="text-align:right;font-weight:700;color:var(--black);"><?php echo formatPrice($customer['total_spent']); ?></td>
                        <td style="text-align:center;">
                            <a href="customer-details.php?id=<?php echo $customer['id']; ?>" class="btn btn-outline btn-sm" style="gap:4px;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:13px;height:13px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
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

<!-- ── Export ── -->
<div class="card" style="padding:20px;">
    <h3 style="font-family:'Cormorant',serif;font-size:16px;font-weight:700;color:var(--black);margin:0 0 14px;text-align:center;">Export Reports</h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
        <button onclick="exportToCSV('product-report')" class="btn btn-outline btn-sm" style="gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:14px;height:14px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Export Products CSV
        </button>
        <button onclick="exportToCSV('customer-report')" class="btn btn-outline btn-sm" style="gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:14px;height:14px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Export Customers CSV
        </button>
        <button onclick="window.print()" class="btn btn-outline btn-sm" style="gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:14px;height:14px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
            </svg>
            Print Report
        </button>
    </div>
</div>

<style>
@media (max-width: 900px) {
    .order-status-bk { grid-template-columns: repeat(3,1fr) !important; }
}
@media (max-width: 640px) {
    .order-status-bk { grid-template-columns: repeat(2,1fr) !important; }
}
@media print {
    .btn, .card form, .export-section { display: none !important; }
}
</style>

<script>
function exportToCSV(reportType) {
    alert('Export functionality — implement CSV export for: ' + reportType);
}
</script>

<?php require_once 'includes/footer.php'; ?>
