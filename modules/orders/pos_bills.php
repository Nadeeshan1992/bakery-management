<?php
// modules/orders/pos_bills.php - POS Counter Bills & Sales History (Separate View)
require_once __DIR__ . '/../../includes/header.php';

requireRole(['admin', 'owner', 'pos_operator']);

$db = getDB();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$paymentMethodFilter = $_GET['payment_method'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

$whereClauses = ["o.order_type = 'pos'", "DATE(o.created_at) BETWEEN ? AND ?"];
$params = [$startDate, $endDate];

if ($paymentMethodFilter !== 'all') {
    $whereClauses[] = "o.payment_method = ?";
    $params[] = $paymentMethodFilter;
}

if (!empty($searchQuery)) {
    $whereClauses[] = "(o.order_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$whereSql = "WHERE " . implode(" AND ", $whereClauses);

$sql = "SELECT o.*, c.name as customer_name, c.phone as customer_phone, u.full_name as created_by_name 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        LEFT JOIN users u ON o.created_by = u.id 
        $whereSql 
        ORDER BY o.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$posBills = $stmt->fetchAll();

// Calculate total POS revenue for this filtered range
$totalPosRevenue = 0;
foreach ($posBills as $b) {
    if ($b['order_status'] !== 'cancelled') {
        $totalPosRevenue += (float)$b['total_amount'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-receipt text-warning me-2"></i> POS Counter Bills & History</h4>
        <p class="text-muted mb-0">Separate dedicated view for instant POS sales, receipts, and counter billing transactions</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>modules/pos/index.php" class="btn btn-warning text-dark fw-bold">
            <i class="fa-solid fa-cash-register me-1"></i> Open POS Counter
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card card-bakery p-3 mb-4">
    <form method="GET" action="" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label text-xs font-weight-bold mb-1">Start Date</label>
            <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($startDate); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label text-xs font-weight-bold mb-1">End Date</label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($endDate); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label text-xs font-weight-bold mb-1">Payment Method</label>
            <select name="payment_method" class="form-select form-select-sm">
                <option value="all" <?php echo ($paymentMethodFilter == 'all') ? 'selected' : ''; ?>>All Methods</option>
                <option value="cash" <?php echo ($paymentMethodFilter == 'cash') ? 'selected' : ''; ?>>Cash</option>
                <option value="cheque" <?php echo ($paymentMethodFilter == 'cheque') ? 'selected' : ''; ?>>Cheque</option>
                <option value="online" <?php echo ($paymentMethodFilter == 'online') ? 'selected' : ''; ?>>Online Transfer</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label text-xs font-weight-bold mb-1">Search Bill # or Customer</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Bill # or name..." value="<?php echo htmlspecialchars($searchQuery); ?>">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
                <i class="fa-solid fa-filter me-1"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Summary Card -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-bakery p-3 border-success shadow-sm">
            <small class="text-muted text-uppercase fw-bold">Total POS Bills Count</small>
            <h3 class="fw-bold text-dark mb-0"><?php echo number_format(count($posBills)); ?> Bills</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-bakery p-3 border-warning shadow-sm">
            <small class="text-muted text-uppercase fw-bold">Total POS Net Revenue</small>
            <h3 class="fw-bold text-success mb-0"><?php echo formatMoney($totalPosRevenue); ?></h3>
        </div>
    </div>
</div>

<!-- POS Bills Table -->
<div class="card card-bakery p-3 shadow-sm">
    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
        <thead class="table-light">
            <tr>
                <th style="width: 15%;">Bill / Receipt #</th>
                <th style="width: 12%;">Date & Time</th>
                <th style="width: 20%;">Customer</th>
                <th class="text-center" style="width: 12%;">Payment</th>
                <th class="text-end" style="width: 12%;">Total Amount</th>
                <th class="text-center" style="width: 10%;">Cashier</th>
                <th class="text-center" style="width: 9%;">Status</th>
                <th class="text-end" style="width: 10%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($posBills)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No POS bills found for the selected date range.</td></tr>
            <?php else: ?>
                <?php foreach ($posBills as $b): ?>
                    <tr>
                        <td><strong class="text-dark"><code><?php echo htmlspecialchars($b['order_number']); ?></code></strong></td>
                        <td>
                            <small class="text-dark fw-semibold d-block"><?php echo date('M d, Y', strtotime($b['created_at'])); ?></small>
                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo date('h:i A', strtotime($b['created_at'])); ?></small>
                        </td>
                        <td>
                            <strong class="text-dark d-block"><?php echo htmlspecialchars($b['customer_name'] ?? 'Walk-in Customer'); ?></strong>
                            <?php if (!empty($b['customer_phone'])): ?>
                                <small class="text-muted" style="font-size: 0.78rem;"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($b['customer_phone']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($b['payment_method']); ?></span>
                            <?php if (!empty($b['cheque_ref'])): ?>
                                <small class="d-block text-primary fw-bold" style="font-size: 0.75rem;">Ref: <?php echo htmlspecialchars($b['cheque_ref']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-bold text-success fs-6"><?php echo formatMoney($b['total_amount']); ?></td>
                        <td class="text-center"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($b['created_by_name'] ?? 'Staff'); ?></span></td>
                        <td class="text-center"><?php echo getStatusBadge($b['order_status']); ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?php echo BASE_URL; ?>modules/pos/invoice.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-primary" target="_blank" title="Print Bill Receipt">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>modules/orders/edit.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-secondary" title="Edit Bill">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>modules/orders/view.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-dark" title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
