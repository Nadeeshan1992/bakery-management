<?php
// modules/inventory/index.php - Raw Material & Ingredient Inventory
require_once __DIR__ . '/../../includes/header.php';

requireRole(['admin', 'owner', 'pos_operator']);

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ingredient'])) {
    $name = trim($_POST['name'] ?? '');
    $unit = trim($_POST['unit'] ?? 'kg');
    $currentStock = floatval($_POST['current_stock'] ?? 0);
    $minStockAlert = floatval($_POST['min_stock_alert'] ?? 5);
    $costPerUnit = floatval($_POST['cost_per_unit'] ?? 0);

    if (!empty($name)) {
        try {
            $stmt = $db->prepare("INSERT INTO ingredients (name, unit, current_stock, min_stock_alert, cost_per_unit) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $unit, $currentStock, $minStockAlert, $costPerUnit]);
            setFlash('success', 'Ingredient "' . htmlspecialchars($name) . '" added to inventory.');
        } catch (Exception $e) {
            setFlash('error', 'Error adding ingredient: ' . $e->getMessage());
        }
    }
}

$ingredients = $db->query("SELECT * FROM ingredients ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Raw Ingredient Inventory</h4>
        <p class="text-muted mb-0">Track bakery raw materials, stock levels, and automated reorder alerts</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>modules/inventory/stock_logs.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-list-check me-1"></i> Inventory Logs
        </a>
        <a href="<?php echo BASE_URL; ?>modules/inventory/stock_in.php" class="btn btn-warning text-dark fw-bold">
            <i class="fa-solid fa-boxes-packing me-1"></i> Stock In / Restock
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Ingredient List Table -->
    <div class="col-lg-8">
        <div class="card card-bakery p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Ingredient Name</th>
                            <th>Current Stock</th>
                            <th>Min Alert Level</th>
                            <th>Cost / Unit</th>
                            <th>Status</th>
                            <th class="text-end">Quick Restock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredients as $ing): ?>
                            <?php $isLow = ($ing['current_stock'] <= $ing['min_stock_alert']); ?>
                            <tr class="<?php echo $isLow ? 'table-warning' : ''; ?>">
                                <td>
                                    <strong class="text-dark"><?php echo htmlspecialchars($ing['name']); ?></strong>
                                </td>
                                <td>
                                    <strong class="fs-6 <?php echo $isLow ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo number_format($ing['current_stock'], 2) . ' ' . $ing['unit']; ?>
                                    </strong>
                                </td>
                                <td><span class="text-muted"><?php echo number_format($ing['min_stock_alert'], 2) . ' ' . $ing['unit']; ?></span></td>
                                <td><?php echo formatMoney($ing['cost_per_unit']) . ' / ' . $ing['unit']; ?></td>
                                <td>
                                    <?php if ($isLow): ?>
                                        <span class="badge bg-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Sufficient</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo BASE_URL; ?>modules/inventory/stock_in.php?ingredient_id=<?php echo $ing['id']; ?>" class="btn btn-sm btn-outline-dark">
                                        <i class="fa-solid fa-plus me-1"></i> Restock
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Add New Raw Material Form -->
    <div class="col-lg-4">
        <div class="card card-bakery p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus-circle text-warning me-2"></i> Add New Ingredient</h5>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Ingredient Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Vanilla Extract" required>
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Unit of Measure *</label>
                    <select name="unit" class="form-select">
                        <option value="kg">Kilogram (kg)</option>
                        <option value="g">Gram (g)</option>
                        <option value="l">Liter (l)</option>
                        <option value="ml">Milliliter (ml)</option>
                        <option value="pcs">Pieces (pcs)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Initial Stock</label>
                    <input type="number" step="0.01" min="0" name="current_stock" class="form-control" value="0.00">
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Min Stock Reorder Alert Level</label>
                    <input type="number" step="0.01" min="0" name="min_stock_alert" class="form-control" value="5.00">
                </div>
                <div class="mb-4">
                    <label class="form-label font-weight-bold">Estimated Cost per Unit (Rs.)</label>
                    <input type="number" step="0.01" min="0" name="cost_per_unit" class="form-control" value="0.00">
                </div>
                <button type="submit" name="add_ingredient" value="1" class="btn btn-warning text-dark fw-bold w-100">
                    <i class="fa-solid fa-save me-1"></i> Save Ingredient
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
