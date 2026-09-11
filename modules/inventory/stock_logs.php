<?php
// modules/inventory/stock_logs.php - Inventory Activity Log
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

$stmt = $db->query("
    SELECT l.*, i.name as ingredient_name, i.unit, u.full_name as user_name 
    FROM inventory_logs l 
    JOIN ingredients i ON l.ingredient_id = i.id 
    JOIN users u ON l.user_id = u.id 
    ORDER BY l.id DESC
");
$logs = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Inventory Movement Logs</h4>
        <p class="text-muted mb-0">Audit history of raw material stock ins, production deductions, and waste</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/inventory/index.php" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Inventory
    </a>
</div>

<div class="card card-bakery p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date & Time</th>
                    <th>Ingredient</th>
                    <th>Log Type</th>
                    <th>Quantity</th>
                    <th>Reason / Notes</th>
                    <th>Logged By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No inventory movement logs recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo formatDateTime($log['created_at']); ?></td>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($log['ingredient_name']); ?></strong></td>
                            <td>
                                <?php if ($log['log_type'] === 'in'): ?>
                                    <span class="badge bg-success">+ STOCK IN</span>
                                <?php elseif ($log['log_type'] === 'production'): ?>
                                    <span class="badge bg-primary">- PRODUCTION</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">- <?php echo strtoupper($log['log_type']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="fs-6 <?php echo ($log['log_type'] === 'in') ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($log['log_type'] === 'in' ? '+' : '-') . number_format($log['quantity'], 2) . ' ' . $log['unit']; ?>
                                </strong>
                            </td>
                            <td><span class="text-muted text-xs"><?php echo htmlspecialchars($log['reason'] ?? 'N/A'); ?></span></td>
                            <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
