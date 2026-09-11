<?php
// modules/reports/index.php - Executive Sales & Financial Reports
require_once __DIR__ . '/../../includes/header.php';

requireRole(['admin']);

$db = getDB();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$salesPersonFilter = isset($_GET['sales_person_id']) && $_GET['sales_person_id'] !== 'all' ? (int)$_GET['sales_person_id'] : 0;

$staffUsers = $db->query("SELECT id, full_name, username, role FROM users ORDER BY full_name ASC")->fetchAll();

$userWhereClause = $salesPersonFilter > 0 ? " AND created_by = " . intval($salesPersonFilter) : "";

// Financial Summary
$stmtSum = $db->prepare("
    SELECT 
        COUNT(*) as total_orders_count,
        COALESCE(SUM(subtotal), 0) as gross_sales,
        COALESCE(SUM(discount), 0) as total_discounts,
        COALESCE(SUM(total_amount), 0) as net_revenue
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ? AND order_status != 'cancelled' {$userWhereClause}
");
$stmtSum->execute([$startDate, $endDate]);
$summary = $stmtSum->fetch();

// Breakdown by Payment Method
$stmtPay = $db->prepare("
    SELECT payment_method, COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as amt 
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ? AND order_status != 'cancelled' {$userWhereClause}
    GROUP BY payment_method
");
$stmtPay->execute([$startDate, $endDate]);
$payBreakdown = $stmtPay->fetchAll();

// Top 5 Selling Products
$stmtTop = $db->prepare("
    SELECT oi.product_name, SUM(oi.quantity) as total_qty, SUM(oi.subtotal) as total_sales 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.order_status != 'cancelled' " . ($salesPersonFilter > 0 ? " AND o.created_by = " . intval($salesPersonFilter) : "") . " 
    GROUP BY oi.product_id 
    ORDER BY total_qty DESC LIMIT 5
");
$stmtTop->execute([$startDate, $endDate]);
$topProducts = $stmtTop->fetchAll();

// Individual Sales Person Sales Value Breakdown
$stmtSalesPerson = $db->prepare("
    SELECT 
        u.id as user_id,
        u.full_name,
        u.username,
        u.role,
        COUNT(o.id) as total_orders,
        COALESCE(SUM(o.subtotal), 0) as gross_sales,
        COALESCE(SUM(o.discount), 0) as total_discounts,
        COALESCE(SUM(o.total_amount), 0) as net_sales,
        COALESCE(SUM(o.paid_amount), 0) as total_collected
    FROM users u
    JOIN orders o ON o.created_by = u.id
    WHERE DATE(o.created_at) BETWEEN ? AND ? 
      AND o.order_status != 'cancelled'
      " . ($salesPersonFilter > 0 ? " AND u.id = " . intval($salesPersonFilter) : "") . "
    GROUP BY u.id
    ORDER BY net_sales DESC
");
$stmtSalesPerson->execute([$startDate, $endDate]);
$salesPersonBreakdown = $stmtSalesPerson->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Sales & Financial Reporting</h4>
        <p class="text-muted mb-0">Analyze revenue, payment channels, and top-performing bakery items</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="fa-solid fa-print me-1"></i> Print Summary Report
    </button>
</div>

<!-- Date & Sales Person Filter Card -->
<div class="card card-bakery p-3 mb-4">
    <form method="GET" action="" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label font-weight-bold">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label font-weight-bold">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label font-weight-bold">Sales Staff Filter</label>
            <select name="sales_person_id" class="form-select">
                <option value="all">-- All Staff Members --</option>
                <?php foreach ($staffUsers as $su): ?>
                    <option value="<?php echo $su['id']; ?>" <?php echo ($salesPersonFilter == $su['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($su['full_name']); ?> (<?php echo strtoupper(str_replace('_', ' ', $su['role'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-warning text-dark fw-bold w-100">
                <i class="fa-solid fa-filter me-1"></i> Generate Report
            </button>
        </div>
    </form>
</div>

<!-- Key Performance Indicators -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card card-bakery p-4 text-center">
            <h6 class="text-muted text-uppercase fw-bold mb-1">Total Orders</h6>
            <h2 class="fw-bold text-dark mb-0"><?php echo number_format($summary['total_orders_count']); ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bakery p-4 text-center">
            <h6 class="text-muted text-uppercase fw-bold mb-1">Gross Sales</h6>
            <h2 class="fw-bold text-dark mb-0"><?php echo formatMoney($summary['gross_sales']); ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bakery p-4 text-center">
            <h6 class="text-muted text-uppercase fw-bold mb-1">Total Discounts</h6>
            <h2 class="fw-bold text-danger mb-0">-<?php echo formatMoney($summary['total_discounts']); ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bakery p-4 text-center border-success">
            <h6 class="text-muted text-uppercase fw-bold mb-1">Net Revenue</h6>
            <h2 class="fw-bold text-success mb-0"><?php echo formatMoney($summary['net_revenue']); ?></h2>
        </div>
    </div>
</div>

<!-- Individual Sales Person Performance Breakdown -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card card-bakery p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-user-tie text-warning me-2"></i> Individual Sales Person Performance Breakdown
                </h5>
                <span class="badge bg-light text-dark border">
                    <i class="fa-solid fa-calendar me-1"></i> <?php echo htmlspecialchars($startDate); ?> to <?php echo htmlspecialchars($endDate); ?>
                </span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Sales Staff Member</th>
                            <th>Role</th>
                            <th class="text-center">Total Orders</th>
                            <th class="text-end">Gross Sales</th>
                            <th class="text-end">Discounts</th>
                            <th class="text-end">Net Sales Value</th>
                            <th class="text-end">Total Collected</th>
                            <th class="text-end">Pending Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salesPersonBreakdown)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No individual sales records found for this date range.</td></tr>
                        <?php else: ?>
                            <?php foreach ($salesPersonBreakdown as $sp): 
                                $pendingBal = max(0, (float)$sp['net_sales'] - (float)$sp['total_collected']);
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
                                    <td class="text-end"><?php echo formatMoney($sp['gross_sales']); ?></td>
                                    <td class="text-end text-danger">-<?php echo formatMoney($sp['total_discounts']); ?></td>
                                    <td class="text-end fw-bold text-success fs-6"><?php echo formatMoney($sp['net_sales']); ?></td>
                                    <td class="text-end text-primary fw-bold"><?php echo formatMoney($sp['total_collected']); ?></td>
                                    <td class="text-end fw-bold <?php echo $pendingBal > 0 ? 'text-danger' : 'text-muted'; ?>">
                                        <?php echo formatMoney($pendingBal); ?>
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

<div class="row g-4">
    <!-- Top Selling Products Table -->
    <div class="col-lg-7">
        <div class="card card-bakery p-4 h-100">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-crown text-warning me-2"></i> Top Selling Bakery Products</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Product Name</th>
                            <th class="text-center">Units Sold</th>
                            <th class="text-end">Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topProducts)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">No sales recorded during this date range.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topProducts as $tp): ?>
                                <tr>
                                    <td><strong class="text-dark"><?php echo htmlspecialchars($tp['product_name']); ?></strong></td>
                                    <td class="text-center"><span class="badge bg-secondary fs-6"><?php echo $tp['total_qty']; ?></span></td>
                                    <td class="text-end fw-bold text-success"><?php echo formatMoney($tp['total_sales']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Breakdown Card -->
    <div class="col-lg-5">
        <div class="card card-bakery p-4 h-100">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-credit-card text-warning me-2"></i> Payment Method Breakdown</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Method</th>
                            <th class="text-center">Orders</th>
                            <th class="text-end">Collected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payBreakdown)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">No transactions.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payBreakdown as $pb): ?>
                                <tr>
                                    <td><strong class="text-uppercase text-dark"><?php echo htmlspecialchars($pb['payment_method']); ?></strong></td>
                                    <td class="text-center"><?php echo $pb['cnt']; ?></td>
                                    <td class="text-end fw-bold text-success"><?php echo formatMoney($pb['amt']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
