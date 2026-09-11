<?php
// modules/dashboard/index.php - Executive Dashboard
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Fetch Dashboard Metrics
$isSalesPerson = (getCurrentUserRole() === 'sales_person');
$userId = getCurrentUserId();

// 1. Today's Sales Total
if ($isSalesPerson) {
    $stmtToday = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as today_sales, COUNT(*) as today_orders FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled' AND created_by = ?");
    $stmtToday->execute([$userId]);
} else {
    $stmtToday = $db->query("SELECT COALESCE(SUM(total_amount), 0) as today_sales, COUNT(*) as today_orders FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'");
}
$todayMetrics = $stmtToday->fetch();
$todaySales = $todayMetrics['today_sales'];
$todayOrders = $todayMetrics['today_orders'];

// 2. Pending & In Production Custom Orders
if ($isSalesPerson) {
    $stmtPending = $db->prepare("SELECT COUNT(*) as pending_count FROM orders WHERE order_status IN ('pending', 'in_production') AND created_by = ?");
    $stmtPending->execute([$userId]);
} else {
    $stmtPending = $db->query("SELECT COUNT(*) as pending_count FROM orders WHERE order_status IN ('pending', 'in_production')");
}
$pendingCount = $stmtPending->fetch()['pending_count'];

// 3. Low Stock Ingredients
$stmtLowStock = $db->query("SELECT * FROM ingredients WHERE current_stock <= min_stock_alert ORDER BY current_stock ASC LIMIT 5");
$lowStockItems = $stmtLowStock->fetchAll();
$lowStockCount = count($lowStockItems);

// Fetch Monthly Expired Goods & Spoilage Loss Metrics
if ($isSalesPerson) {
    $stmtWaste = $db->prepare("
        SELECT COALESCE(SUM(quantity), 0) as waste_qty, COALESCE(SUM(total_value), 0) as waste_val 
        FROM order_returns 
        WHERE reason = 'expired' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND created_by = ?
    ");
    $stmtWaste->execute([$userId]);
} else {
    $stmtWaste = $db->query("
        SELECT COALESCE(SUM(quantity), 0) as waste_qty, COALESCE(SUM(total_value), 0) as waste_val 
        FROM order_returns 
        WHERE reason = 'expired' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
    ");
}
$wasteMetrics = $stmtWaste->fetch();
$wasteQty = $wasteMetrics['waste_qty'];
$wasteVal = $wasteMetrics['waste_val'];

// 4. Recent 5 Orders
if ($isSalesPerson) {
    $stmtRecent = $db->prepare("SELECT o.*, c.name as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.created_by = ? ORDER BY o.id DESC LIMIT 5");
    $stmtRecent->execute([$userId]);
} else {
    $stmtRecent = $db->query("SELECT o.*, c.name as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id ORDER BY o.id DESC LIMIT 5");
}
$recentOrders = $stmtRecent->fetchAll();

// 5. Chart Data: Past 7 Days Sales
if ($isSalesPerson) {
    $stmtChart = $db->prepare("
        SELECT DATE_FORMAT(created_at, '%b %d') as order_date, COALESCE(SUM(total_amount), 0) as daily_total 
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND order_status != 'cancelled' AND created_by = ?
        GROUP BY DATE(created_at) 
        ORDER BY DATE(created_at) ASC
    ");
    $stmtChart->execute([$userId]);
} else {
    $stmtChart = $db->query("
        SELECT DATE_FORMAT(created_at, '%b %d') as order_date, COALESCE(SUM(total_amount), 0) as daily_total 
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND order_status != 'cancelled'
        GROUP BY DATE(created_at) 
        ORDER BY DATE(created_at) ASC
    ");
}
$chartRawData = $stmtChart->fetchAll();

// 6. Staff Sales Performance (This Month for Admin / Owner)
$staffSalesPerformance = [];
if (!$isSalesPerson) {
    $stmtStaffPerf = $db->query("
        SELECT 
            u.full_name,
            u.username,
            u.role,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as net_sales,
            COALESCE(SUM(o.paid_amount), 0) as total_collected
        FROM users u
        JOIN orders o ON o.created_by = u.id
        WHERE MONTH(o.created_at) = MONTH(CURDATE()) 
          AND YEAR(o.created_at) = YEAR(CURDATE())
          AND o.order_status != 'cancelled'
        GROUP BY u.id
        ORDER BY net_sales DESC
    ");
    $staffSalesPerformance = $stmtStaffPerf->fetchAll();
}

$chartLabels = [];
$chartValues = [];

// Fill last 7 days defaults
for ($i = 6; $i >= 0; $i--) {
    $d = date('b d', strtotime("-$i days"));
    $chartLabels[] = date('M d', strtotime("-$i days"));
    $chartValues[] = 0;
}
foreach ($chartRawData as $row) {
    $idx = array_search($row['order_date'], $chartLabels);
    if ($idx !== false) {
        $chartValues[$idx] = (float)$row['daily_total'];
    }
}
?>

<div class="row g-4 mb-4">
    <!-- Stat 1: Today's Sales -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-bakery-orange">
            <div>
                <h6 class="text-white-50 text-uppercase fw-bold mb-1 text-truncate">Today's Revenue</h6>
                <div class="stat-value text-white"><?php echo formatMoney($todaySales); ?></div>
            </div>
            <small class="text-white-50 text-truncate"><i class="fa-solid fa-cart-shopping me-1"></i> <?php echo $todayOrders; ?> order(s) today</small>
            <i class="fa-solid fa-dollar-sign stat-icon"></i>
        </div>
    </div>

    <!-- Stat 2: Active Orders -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-bakery-chocolate">
            <div>
                <h6 class="text-white-50 text-uppercase fw-bold mb-1 text-truncate">Active Orders</h6>
                <div class="stat-value text-white"><?php echo $pendingCount; ?></div>
            </div>
            <small class="text-white-50 text-truncate"><i class="fa-solid fa-cake-candles me-1"></i> Pre-Orders</small>
            <i class="fa-solid fa-kitchen-set stat-icon"></i>
        </div>
    </div>

    <!-- Stat 3: Low Stock Ingredients -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-bakery-cream">
            <div>
                <h6 class="text-white-50 text-uppercase fw-bold mb-1 text-truncate">Low Stock Alert</h6>
                <div class="stat-value text-white"><?php echo $lowStockCount; ?></div>
            </div>
            <small class="text-white-50 text-truncate"><i class="fa-solid fa-triangle-exclamation me-1"></i> Restock alert triggered</small>
            <i class="fa-solid fa-wheat-awn stat-icon"></i>
        </div>
    </div>

    <!-- Stat 4: Quick Action -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-bakery-teal">
            <?php if (getCurrentUserRole() === 'sales_person'): ?>
                <div>
                    <h6 class="text-white-50 text-uppercase fw-bold mb-1 text-truncate">Book Pre-Order</h6>
                    <small class="text-white d-block text-truncate">Pre-order booking & returns</small>
                </div>
                <a href="<?php echo BASE_URL; ?>modules/orders/add.php" class="btn btn-light text-teal font-weight-bold text-nowrap w-100 text-center py-1" style="font-size: 0.85rem;">
                    <i class="fa-solid fa-calendar-plus me-1"></i> Book New Pre-Order
                </a>
                <i class="fa-solid fa-calendar-check stat-icon"></i>
            <?php else: ?>
                <div>
                    <h6 class="text-white-50 text-uppercase fw-bold mb-1 text-truncate">Counter POS</h6>
                    <small class="text-white d-block text-truncate">Serve walk-in customers</small>
                </div>
                <a href="<?php echo BASE_URL; ?>modules/pos/index.php" class="btn btn-light text-teal font-weight-bold text-nowrap w-100 text-center py-1" style="font-size: 0.85rem;">
                    <i class="fa-solid fa-cash-register me-1"></i> Open Counter POS
                </a>
                <i class="fa-solid fa-receipt stat-icon"></i>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Sales Chart -->
    <div class="col-lg-8">
        <div class="card card-bakery p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-chart-area text-warning me-2"></i> Sales Trend (Last 7 Days)</h5>
                <span class="badge bg-light text-dark border">Daily Revenue</span>
            </div>
            <div style="height: 280px; position: relative;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts & Low Stock Warning -->
    <div class="col-lg-4">
        <div class="card card-bakery p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-bolt text-warning me-2"></i> Quick Actions</h5>
            <div class="d-grid gap-2">
                <a href="<?php echo BASE_URL; ?>modules/orders/add.php" class="btn btn-outline-warning text-dark text-start py-2">
                    <i class="fa-solid fa-plus-circle me-2 text-warning"></i> Book Pre-Order
                </a>
                <a href="<?php echo BASE_URL; ?>modules/inventory/stock_in.php" class="btn btn-outline-secondary text-start py-2">
                    <i class="fa-solid fa-boxes-packing me-2 text-primary"></i> Stock In Raw Material
                </a>
                <a href="<?php echo BASE_URL; ?>modules/products/add.php" class="btn btn-outline-secondary text-start py-2">
                    <i class="fa-solid fa-bread-slice me-2 text-danger"></i> Add New Bakery Product
                </a>
            </div>
        </div>

        <!-- Expired Goods / Spoilage Waste Tracker -->
        <div class="card card-bakery p-4 mb-4 border-danger-subtle bg-light">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-danger mb-0">
                    <i class="fa-solid fa-trash-can me-1"></i> Expired Goods Waste
                </h6>
                <span class="badge bg-danger">This Month</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <div class="text-muted text-xs">Total Expired Items</div>
                    <strong class="fs-5 text-dark"><?php echo number_format($wasteQty, 0); ?> pcs</strong>
                </div>
                <div class="text-end">
                    <div class="text-muted text-xs">Financial Write-Off</div>
                    <strong class="fs-5 text-danger"><?php echo formatMoney($wasteVal); ?></strong>
                </div>
            </div>
        </div>

        <?php if ($lowStockCount > 0): ?>
        <div class="card card-bakery p-4 border-danger">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-danger mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i> Inventory Alert</h6>
                <a href="<?php echo BASE_URL; ?>modules/inventory/index.php" class="text-sm text-decoration-none">View All</a>
            </div>
            <ul class="list-group list-group-flush text-sm">
                <?php foreach ($lowStockItems as $item): ?>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                            <div class="text-muted" style="font-size: 0.8rem;">Threshold: <?php echo $item['min_stock_alert'] . ' ' . $item['unit']; ?></div>
                        </div>
                        <span class="badge bg-danger"><?php echo $item['current_stock'] . ' ' . $item['unit']; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Orders Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-bakery p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i> Recent Orders</h5>
                <a href="<?php echo BASE_URL; ?>modules/orders/index.php" class="btn btn-sm btn-outline-secondary">View All Orders</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No orders placed yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><strong class="text-dark"><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?php echo strtoupper(str_replace('_', ' ', $order['order_type'])); ?>
                                        </span>
                                    </td>
                                    <td><strong class="text-success"><?php echo formatMoney($order['total_amount']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-secondary text-uppercase"><?php echo $order['payment_method']; ?></span>
                                    </td>
                                    <td><?php echo getStatusBadge($order['order_status']); ?></td>
                                    <td><?php echo formatDateTime($order['created_at']); ?></td>
                                    <td class="text-end">
                                        <a href="<?php echo BASE_URL; ?>modules/orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-light border">
                                            <i class="fa-solid fa-eye me-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!$isSalesPerson && !empty($staffSalesPerformance)): ?>
<!-- Staff Sales Performance Summary (This Month) -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-bakery p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-user-tie text-warning me-2"></i> Individual Sales Value by Staff / Sales Person (This Month)
                </h5>
                <a href="<?php echo BASE_URL; ?>modules/reports/index.php" class="btn btn-sm btn-outline-warning text-dark fw-bold">
                    View Full Reports <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Sales Staff Member</th>
                            <th>Role</th>
                            <th class="text-center">Total Orders</th>
                            <th class="text-end">Total Sales Value</th>
                            <th class="text-end">Amount Collected</th>
                            <th class="text-end">Pending Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffSalesPerformance as $sp): 
                            $pending = max(0, (float)$sp['net_sales'] - (float)$sp['total_collected']);
                        ?>
                            <tr>
                                <td>
                                    <strong class="text-dark fs-6"><?php echo htmlspecialchars($sp['full_name']); ?></strong>
                                    <small class="text-muted d-block">@<?php echo htmlspecialchars($sp['username']); ?></small>
                                </td>
                                <td>
                                    <span class="badge <?php echo $sp['role'] === 'sales_person' ? 'bg-primary' : ($sp['role'] === 'pos_operator' ? 'bg-info text-dark' : 'bg-dark'); ?>">
                                        <?php echo strtoupper(str_replace('_', ' ', $sp['role'])); ?>
                                    </span>
                                </td>
                                <td class="text-center fw-bold fs-6"><?php echo number_format($sp['total_orders']); ?></td>
                                <td class="text-end fw-bold text-success fs-6"><?php echo formatMoney($sp['net_sales']); ?></td>
                                <td class="text-end text-primary fw-bold"><?php echo formatMoney($sp['total_collected']); ?></td>
                                <td class="text-end fw-bold <?php echo $pending > 0 ? 'text-danger' : 'text-muted'; ?>">
                                    <?php echo formatMoney($pending); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Sales (LKR)',
                data: <?php echo json_encode($chartValues); ?>,
                borderColor: '#d97724',
                backgroundColor: 'rgba(217, 119, 36, 0.1)',
                fill: true,
                tension: 0.3,
                borderWidth: 3,
                pointBackgroundColor: '#8c4a27'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return 'Rs. ' + value; }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
