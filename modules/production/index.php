<?php
// modules/production/index.php - Batch Production & Recipe Deductions
require_once __DIR__ . '/../../includes/header.php';

requireRole(['admin', 'owner', 'pos_operator']);

$db = getDB();

$products = $db->query("SELECT * FROM products WHERE status = 'active' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_production'])) {
    $productId = intval($_POST['product_id'] ?? 0);
    $batchQty = intval($_POST['batch_quantity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if ($productId <= 0 || $batchQty <= 0) {
        setFlash('error', 'Please select a product and valid batch quantity.');
    } else {
        try {
            $db->beginTransaction();

            // Fetch recipe for this product
            $stmtR = $db->prepare("SELECT r.*, i.name as ingredient_name, i.current_stock, i.unit FROM recipes r JOIN ingredients i ON r.ingredient_id = i.id WHERE r.product_id = ?");
            $stmtR->execute([$productId]);
            $recipeItems = $stmtR->fetchAll();

            // Check if sufficient ingredient stock exists for this batch size
            $insufficientStock = [];
            foreach ($recipeItems as $rItem) {
                $requiredTotal = floatval($rItem['quantity']) * $batchQty;
                if (floatval($rItem['current_stock']) < $requiredTotal) {
                    $insufficientStock[] = $rItem['ingredient_name'] . ' (Required: ' . $requiredTotal . ' ' . $rItem['unit'] . ', Available: ' . $rItem['current_stock'] . ' ' . $rItem['unit'] . ')';
                }
            }

            if (!empty($insufficientStock)) {
                $db->rollBack();
                setFlash('error', 'Cannot process batch production due to low stock: ' . implode('; ', $insufficientStock));
            } else {
                // Deduct ingredients and log
                $stmtDeduct = $db->prepare("UPDATE ingredients SET current_stock = current_stock - ? WHERE id = ?");
                $stmtLog = $db->prepare("INSERT INTO inventory_logs (ingredient_id, log_type, quantity, reason, user_id) VALUES (?, 'production', ?, ?, ?)");

                foreach ($recipeItems as $rItem) {
                    $deductQty = floatval($rItem['quantity']) * $batchQty;
                    $stmtDeduct->execute([$deductQty, $rItem['ingredient_id']]);
                    $stmtLog->execute([
                        $rItem['ingredient_id'],
                        $deductQty,
                        'Batch production of ' . $batchQty . ' unit(s)',
                        getCurrentUserId()
                    ]);
                }

                // Log production batch
                $stmtProd = $db->prepare("INSERT INTO production_logs (product_id, batch_quantity, produced_by, notes) VALUES (?, ?, ?, ?)");
                $stmtProd->execute([$productId, $batchQty, getCurrentUserId(), $notes]);

                $db->commit();
                setFlash('success', 'Batch production of ' . $batchQty . ' unit(s) logged successfully! Ingredients deducted from stock.');
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setFlash('error', 'Production logging failed: ' . $e->getMessage());
        }
    }
}

// Fetch Recent Production Logs
$productionHistory = $db->query("
    SELECT pl.*, p.name as product_name, u.full_name as baker_name 
    FROM production_logs pl 
    JOIN products p ON pl.product_id = p.id 
    JOIN users u ON pl.produced_by = u.id 
    ORDER BY pl.id DESC LIMIT 10
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Kitchen Production & Batch Baking</h4>
        <p class="text-muted mb-0">Record baking runs to automatically deduct raw ingredients based on product recipes</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/production/recipes.php" class="btn btn-warning text-dark fw-bold">
        <i class="fa-solid fa-book-open me-1"></i> Manage Recipes (BOM)
    </a>
</div>

<div class="row g-4">
    <!-- Log Production Form -->
    <div class="col-lg-5">
        <div class="card card-bakery p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-fire-burner text-warning me-2"></i> Log Daily Production Run</h5>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Select Baked Item *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">-- Select Product --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Batch Units Baked *</label>
                    <input type="number" name="batch_quantity" class="form-control" value="1" min="1" required>
                    <small class="text-muted">Ingredient stock will be deducted proportionately based on recipe.</small>
                </div>
                <div class="mb-4">
                    <label class="form-label font-weight-bold">Notes / Shift Details</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Morning shift baking batch #1"></textarea>
                </div>
                <button type="submit" name="log_production" value="1" class="btn btn-warning text-dark fw-bold w-100 py-2">
                    <i class="fa-solid fa-check-double me-1"></i> Confirm & Deduct Ingredients
                </button>
            </form>
        </div>
    </div>

    <!-- Production History -->
    <div class="col-lg-7">
        <div class="card card-bakery p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i> Recent Baking History</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Time</th>
                            <th>Product Baked</th>
                            <th class="text-center">Batch Size</th>
                            <th>Baker</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productionHistory)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No production runs recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($productionHistory as $ph): ?>
                                <tr>
                                    <td><?php echo formatDateTime($ph['created_at']); ?></td>
                                    <td><strong class="text-dark"><?php echo htmlspecialchars($ph['product_name']); ?></strong></td>
                                    <td class="text-center"><span class="badge bg-primary fs-6"><?php echo $ph['batch_quantity']; ?> units</span></td>
                                    <td><?php echo htmlspecialchars($ph['baker_name']); ?></td>
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
