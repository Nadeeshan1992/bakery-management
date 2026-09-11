<?php
// modules/inventory/stock_in.php - Stock In / Replenish Form
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();
$selectedIngId = isset($_GET['ingredient_id']) ? (int)$_GET['ingredient_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ingredientId = intval($_POST['ingredient_id'] ?? 0);
    $quantity = floatval($_POST['quantity'] ?? 0);
    $logType = $_POST['log_type'] ?? 'in';
    $reason = trim($_POST['reason'] ?? '');

    if ($ingredientId <= 0 || $quantity <= 0) {
        setFlash('error', 'Please select an ingredient and enter a valid quantity greater than zero.');
    } else {
        try {
            $db->beginTransaction();

            // Update current stock
            if ($logType === 'in') {
                $stmtUpdate = $db->prepare("UPDATE ingredients SET current_stock = current_stock + ? WHERE id = ?");
            } else {
                $stmtUpdate = $db->prepare("UPDATE ingredients SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?");
            }
            $stmtUpdate->execute([$quantity, $ingredientId]);

            // Log entry
            $stmtLog = $db->prepare("INSERT INTO inventory_logs (ingredient_id, log_type, quantity, reason, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmtLog->execute([$ingredientId, $logType, $quantity, $reason, getCurrentUserId()]);

            $db->commit();

            setFlash('success', 'Stock movement recorded successfully!');
            header('Location: ' . BASE_URL . 'modules/inventory/index.php');
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setFlash('error', 'Stock update failed: ' . $e->getMessage());
        }
    }
}

$ingredients = $db->query("SELECT * FROM ingredients ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card card-bakery p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0"><i class="fa-solid fa-boxes-packing text-warning me-2"></i> Stock Movement Form</h4>
                <a href="<?php echo BASE_URL; ?>modules/inventory/index.php" class="btn btn-outline-secondary btn-sm">Back to Inventory</a>
            </div>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Movement Type *</label>
                    <select name="log_type" class="form-select" required>
                        <option value="in">+ Stock In (New Purchase / Restock)</option>
                        <option value="out">- Stock Out (Manual Usage)</option>
                        <option value="waste">- Spoilage / Damaged Waste</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold">Select Ingredient *</label>
                    <select name="ingredient_id" class="form-select" required>
                        <option value="">-- Choose Ingredient --</option>
                        <?php foreach ($ingredients as $ing): ?>
                            <option value="<?php echo $ing['id']; ?>" <?php echo ($ing['id'] == $selectedIngId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ing['name']); ?> (Current Stock: <?php echo $ing['current_stock'] . ' ' . $ing['unit']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold">Quantity to Add / Remove *</label>
                    <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" placeholder="e.g. 10.00" required>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold">Reason / Supplier Invoice Reference</label>
                    <input type="text" name="reason" class="form-control" placeholder="e.g. Supplier PO #4092 or Broken Eggs log">
                </div>

                <button type="submit" class="btn btn-warning text-dark fw-bold w-100 py-2">
                    <i class="fa-solid fa-check me-1"></i> Submit Stock Log
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
