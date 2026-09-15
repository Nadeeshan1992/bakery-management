<?php
// modules/orders/index.php - Dedicated Pre-Orders Management View
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

$whereClauses = ["o.order_type = 'preorder'"];
$params = [];

if (getCurrentUserRole() === 'sales_person') {
    $whereClauses[] = "o.created_by = ?";
    $params[] = getCurrentUserId();
}

if ($statusFilter !== 'all') {
    $whereClauses[] = "o.order_status = ?";
    $params[] = $statusFilter;
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
$orders = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-calendar-check text-warning me-2"></i> Pre-Orders Management</h4>
        <p class="text-muted mb-0">
            <?php if (getCurrentUserRole() === 'sales_person'): ?>
                <span class="badge bg-info text-dark me-1"><i class="fa-solid fa-user me-1"></i> Your Orders Only</span>
                Viewing your assigned advance pre-orders
            <?php else: ?>
                Dedicated view for customer pre-orders, baking schedules, and delivery balances
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>modules/orders/add.php" class="btn btn-warning text-dark fw-bold">
            <i class="fa-solid fa-plus-circle me-1"></i> Book New Pre-Order
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card card-bakery p-3 mb-4">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-md-5">
            <label class="form-label text-xs font-weight-bold mb-1">Search Pre-Order # or Customer</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search order #, customer name, phone..." value="<?php echo htmlspecialchars($searchQuery); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label text-xs font-weight-bold mb-1">Workflow Status</label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="all" <?php echo ($statusFilter == 'all') ? 'selected' : ''; ?>>All Workflow Statuses</option>
                <option value="pending" <?php echo ($statusFilter == 'pending') ? 'selected' : ''; ?>>Pending (Baking Queue)</option>
                <option value="in_production" <?php echo ($statusFilter == 'in_production') ? 'selected' : ''; ?>>In Production (Baking)</option>
                <option value="ready" <?php echo ($statusFilter == 'ready') ? 'selected' : ''; ?>>Ready for Pickup/Delivery</option>
                <option value="completed" <?php echo ($statusFilter == 'completed') ? 'selected' : ''; ?>>Completed & Delivered</option>
                <option value="cancelled" <?php echo ($statusFilter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-3 text-end pt-3">
            <button type="submit" class="btn btn-sm btn-primary me-1 fw-bold">Filter</button>
            <a href="<?php echo BASE_URL; ?>modules/orders/index.php" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="card card-bakery p-3 shadow-sm border-0">
    <div class="table-responsive-lg">
        <table class="table table-hover align-middle mb-0" style="table-layout: fixed; width: 100%;">
            <thead class="table-light">
                <tr>
                    <th style="width: 14%;">Pre-Order #</th>
                    <th style="width: 20%;">Customer</th>
                    <th style="width: 18%;">Delivery Date</th>
                    <th class="text-end" style="width: 14%;">Total Amount</th>
                    <th class="text-center" style="width: 16%;">Payment</th>
                    <th class="text-center" style="width: 18%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No pre-orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): 
                        $balDue = max(0, (float)$o['total_amount'] - (float)$o['paid_amount']);
                        $delivTimestamp = $o['delivery_date'] ? strtotime($o['delivery_date']) : false;
                    ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><code><?php echo htmlspecialchars($o['order_number']); ?></code></strong>
                                <span class="d-block mt-1"><?php echo getStatusBadge($o['order_status']); ?></span>
                            </td>
                            <td>
                                <strong class="text-dark d-block text-truncate"><?php echo htmlspecialchars($o['customer_name'] ?? 'Walk-in'); ?></strong>
                                <?php if (!empty($o['customer_phone'])): ?>
                                    <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($o['customer_phone']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($delivTimestamp): ?>
                                    <span class="text-primary fw-semibold d-block"><i class="fa-regular fa-calendar me-1"></i><?php echo date('M d, Y', $delivTimestamp); ?></span>
                                    <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?php echo date('h:i A', $delivTimestamp); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Not Specified</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <strong class="text-dark fs-6"><?php echo formatMoney($o['total_amount']); ?></strong>
                            </td>
                            <td class="text-center">
                                <?php if ($balDue > 0): ?>
                                    <span class="badge bg-warning text-dark text-uppercase mb-1">PARTIAL</span>
                                    <small class="text-danger fw-bold d-block">Due: <?php echo formatMoney($balDue); ?></small>
                                <?php else: ?>
                                    <span class="badge bg-success text-uppercase mb-1">PAID</span>
                                    <small class="text-muted text-uppercase d-block"><?php echo htmlspecialchars($o['payment_method']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?php echo BASE_URL; ?>modules/pos/invoice.php?id=<?php echo $o['id']; ?>" class="btn btn-outline-primary" target="_blank" data-bs-toggle="tooltip" title="Print Pre-Order Invoice">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    <?php if ($balDue > 0): ?>
                                        <a href="<?php echo BASE_URL; ?>modules/orders/view.php?id=<?php echo $o['id']; ?>" class="btn btn-success text-white" data-bs-toggle="tooltip" title="Settle Balance">
                                            <i class="fa-solid fa-hand-holding-dollar"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo BASE_URL; ?>modules/orders/edit.php?id=<?php echo $o['id']; ?>" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Edit Order">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>modules/orders/view.php?id=<?php echo $o['id']; ?>" class="btn btn-outline-info" data-bs-toggle="tooltip" title="View Details">
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
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
