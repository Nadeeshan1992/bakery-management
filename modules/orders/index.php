<?php
// modules/orders/index.php - Order Tracking & Management
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

$typeFilter = $_GET['type'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';

$whereClauses = [];
$params = [];

if (getCurrentUserRole() === 'sales_person') {
    $whereClauses[] = "o.created_by = ?";
    $params[] = getCurrentUserId();
}

if ($typeFilter !== 'all') {
    $whereClauses[] = "o.order_type = ?";
    $params[] = $typeFilter;
}

if ($statusFilter !== 'all') {
    $whereClauses[] = "o.order_status = ?";
    $params[] = $statusFilter;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$sql = "SELECT o.*, c.name as customer_name, c.phone as customer_phone, u.full_name as created_by_name 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        LEFT JOIN users u ON o.created_by = u.id 
        $whereSql 
        ORDER BY o.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Pre-Orders</h4>
        <p class="text-muted mb-0">
            <?php if (getCurrentUserRole() === 'sales_person'): ?>
                <span class="badge bg-info text-dark me-1"><i class="fa-solid fa-user me-1"></i> Your Orders Only</span>
                Viewing your assigned pre-orders
            <?php else: ?>
                Track baking schedules, delivery dates, and order status workflow
            <?php endif; ?>
        </p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/orders/add.php" class="btn btn-warning text-dark fw-bold">
        <i class="fa-solid fa-plus-circle me-1"></i> Book New Pre-Order
    </a>
</div>

<!-- Filters Bar -->
<div class="card card-bakery p-3 mb-4">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-md-4">
            <label class="form-label text-xs font-weight-bold mb-1">Order Type</label>
            <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="all" <?php echo ($typeFilter == 'all') ? 'selected' : ''; ?>>All Order Types</option>
                <option value="preorder" <?php echo ($typeFilter == 'preorder') ? 'selected' : ''; ?>>Pre-Order</option>
                <option value="pos" <?php echo ($typeFilter == 'pos') ? 'selected' : ''; ?>>Counter POS</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label text-xs font-weight-bold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="all" <?php echo ($statusFilter == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                <option value="pending" <?php echo ($statusFilter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="in_production" <?php echo ($statusFilter == 'in_production') ? 'selected' : ''; ?>>In Production</option>
                <option value="ready" <?php echo ($statusFilter == 'ready') ? 'selected' : ''; ?>>Ready for Pickup/Delivery</option>
                <option value="completed" <?php echo ($statusFilter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo ($statusFilter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-4 text-end pt-3">
            <a href="<?php echo BASE_URL; ?>modules/orders/index.php" class="btn btn-sm btn-outline-secondary">Reset Filters</a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="card card-bakery p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Order Type</th>
                    <th>Delivery Date</th>
                    <th>Total</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No matching orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): 
                        $balDue = max(0, (float)$o['total_amount'] - (float)$o['paid_amount']);
                    ?>
                        <tr>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($o['customer_name'] ?? 'Walk-in'); ?></strong>
                                <?php if (!empty($o['customer_phone'])): ?>
                                    <div class="text-muted text-xs"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($o['customer_phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?php echo strtoupper(str_replace('_', ' ', $o['order_type'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($o['delivery_date']): ?>
                                    <span class="text-primary fw-semibold"><i class="fa-regular fa-calendar me-1"></i><?php echo formatDateTime($o['delivery_date']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Instant Counter</span>
                                <?php endif; ?>
                            </td>
                            <td><strong class="text-dark"><?php echo formatMoney($o['total_amount']); ?></strong></td>
                            <td>
                                <?php if ($balDue > 0): ?>
                                    <span class="badge bg-warning text-dark text-uppercase d-block mb-1">PARTIAL PAID</span>
                                    <small class="text-danger fw-bold d-block">Due: <?php echo formatMoney($balDue); ?></small>
                                <?php else: ?>
                                    <span class="badge bg-success text-uppercase d-block mb-1">PAID</span>
                                    <small class="text-muted text-uppercase d-block"><?php echo htmlspecialchars($o['payment_method']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo getStatusBadge($o['order_status']); ?></td>
                            <td class="text-end">
                                <?php if ($balDue > 0): ?>
                                    <a href="<?php echo BASE_URL; ?>modules/orders/view.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-success me-1 text-white fw-bold">
                                        <i class="fa-solid fa-hand-holding-dollar me-1"></i> Settle
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>modules/orders/view.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-light border">
                                    <i class="fa-solid fa-eye me-1"></i> Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
