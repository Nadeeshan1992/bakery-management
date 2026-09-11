<?php
// modules/products/daily_stock.php - Bakery Product Stock In Entry (Individual & Bulk)
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireRole(['admin', 'owner', 'pos_operator']);

$db = getDB();

// 1. Process Individual Single Product Stock Entry (Top Quick Form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_single_product_stock'])) {
    $productId = intval($_POST['single_product_id'] ?? 0);
    $addedQty = floatval($_POST['single_added_qty'] ?? 0);
    $mode = $_POST['stock_mode'] ?? 'add';
    $notes = trim($_POST['single_notes'] ?? 'Individual item stock entry');

    if ($productId > 0 && ($addedQty > 0 || $mode === 'set')) {
        $stmtP = $db->prepare("SELECT name, current_stock, unit FROM products WHERE id = ?");
        $stmtP->execute([$productId]);
        $prod = $stmtP->fetch();

        if ($prod) {
            if ($mode === 'set') {
                $diff = $addedQty - (float)$prod['current_stock'];
                $stmtUpdate = $db->prepare("UPDATE products SET current_stock = ? WHERE id = ?");
                $stmtUpdate->execute([$addedQty, $productId]);
                
                $stmtLog = $db->prepare("
                    INSERT INTO product_stock_logs (product_id, log_type, quantity, notes, created_by) 
                    VALUES (?, 'manual_adjustment', ?, ?, ?)
                ");
                $stmtLog->execute([$productId, $diff, $notes, getCurrentUserId()]);

                setFlash('success', "Stock for " . $prod['name'] . " set to " . number_format($addedQty, 0) . " {$prod['unit']} successfully!");
            } else {
                $stmtUpdate = $db->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
                $stmtUpdate->execute([$addedQty, $productId]);
                
                $stmtLog = $db->prepare("
                    INSERT INTO product_stock_logs (product_id, log_type, quantity, notes, created_by) 
                    VALUES (?, 'daily_batch', ?, ?, ?)
                ");
                $stmtLog->execute([$productId, $addedQty, $notes, getCurrentUserId()]);

                $newStock = (float)$prod['current_stock'] + $addedQty;
                setFlash('success', "Added +" . number_format($addedQty, 0) . " {$prod['unit']} to " . $prod['name'] . " stock! (New Total: " . number_format($newStock, 0) . " {$prod['unit']})");
            }
        }
    } else {
        setFlash('error', 'Please select a valid product and enter a quantity.');
    }

    header('Location: ' . BASE_URL . 'modules/products/daily_stock.php');
    exit;
}

// 2. Process Individual Row Stock Entry (Row Button Click)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_row_stock'])) {
    $productId = intval($_POST['submit_row_stock']);
    $addedQty = floatval($_POST['row_added_stock'][$productId] ?? 0);
    $notes = trim($_POST['batch_notes'] ?? 'Individual item stock entry');

    if ($productId > 0 && $addedQty > 0) {
        $stmtP = $db->prepare("SELECT name, current_stock, unit FROM products WHERE id = ?");
        $stmtP->execute([$productId]);
        $prod = $stmtP->fetch();

        if ($prod) {
            $stmtUpdate = $db->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
            $stmtUpdate->execute([$addedQty, $productId]);
            
            $stmtLog = $db->prepare("
                INSERT INTO product_stock_logs (product_id, log_type, quantity, notes, created_by) 
                VALUES (?, 'daily_batch', ?, ?, ?)
            ");
            $stmtLog->execute([$productId, $addedQty, $notes, getCurrentUserId()]);

            $newStock = (float)$prod['current_stock'] + $addedQty;
            setFlash('success', "Added +" . number_format($addedQty, 0) . " {$prod['unit']} to " . $prod['name'] . " stock! (New Total: " . number_format($newStock, 0) . " {$prod['unit']})");
        }
    } else {
        setFlash('error', 'Please enter a valid stock quantity for the selected item.');
    }

    header('Location: ' . BASE_URL . 'modules/products/daily_stock.php');
    exit;
}

// 3. Process Bulk Daily Stock Entry (All rows submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_daily_stock'])) {
    $stockInputs = $_POST['added_stock'] ?? [];
    $notes = trim($_POST['batch_notes'] ?? 'Daily morning baking stock entry');
    $updatedCount = 0;

    try {
        $db->beginTransaction();

        $stmtUpdate = $db->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
        $stmtLog = $db->prepare("
            INSERT INTO product_stock_logs (product_id, log_type, quantity, notes, created_by) 
            VALUES (?, 'daily_batch', ?, ?, ?)
        ");

        foreach ($stockInputs as $productId => $addedQty) {
            $productId = intval($productId);
            $addedQty = floatval($addedQty);

            if ($productId > 0 && $addedQty > 0) {
                $stmtUpdate->execute([$addedQty, $productId]);
                $stmtLog->execute([$productId, $addedQty, $notes, getCurrentUserId()]);
                $updatedCount++;
            }
        }

        $db->commit();

        if ($updatedCount > 0) {
            setFlash('success', 'Daily baking stock batch added successfully! (' . $updatedCount . ' items updated)');
        } else {
            setFlash('info', 'No stock quantities were entered.');
        }

        header('Location: ' . BASE_URL . 'modules/products/daily_stock.php');
        exit;

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        setFlash('danger', 'Failed to update daily stock: ' . $e->getMessage());
    }
}

// Fetch Active Products with Categories
$products = $db->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY c.name ASC, p.name ASC
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-boxes-stacked text-warning me-2"></i> Daily Bakery Item Stock Entry</h4>
        <p class="text-muted mb-0">Enter stock quantities individually per item or update morning baked items</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>modules/products/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-list me-1"></i> Products Catalog
        </a>
        <?php if (in_array(getCurrentUserRole(), ['admin', 'owner', 'pos_operator'])): ?>
        <a href="<?php echo BASE_URL; ?>modules/pos/index.php" class="btn btn-warning text-dark btn-sm fw-bold">
            <i class="fa-solid fa-cash-register me-1"></i> Open Counter POS
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 1: INDIVIDUAL ITEM QUICK STOCK ENTRY FORM -->
<div class="card card-bakery p-4 shadow-sm border-0 mb-4 bg-white" style="border-left: 5px solid #0d6efd !important;">
    <h5 class="fw-bold text-primary mb-3">
        <i class="fa-solid fa-plus-circle me-2"></i> Individual Item Quick Stock Update
    </h5>
    <form method="POST" action="" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label font-weight-bold">Select Item / Product:</label>
            <select name="single_product_id" id="single_product_id" class="form-select select2" required>
                <option value="">-- Choose Bakery Item --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>">
                        <?php echo htmlspecialchars($p['name']); ?> (Current Stock: <?php echo number_format($p['current_stock'], 0) . ' ' . htmlspecialchars($p['unit']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label font-weight-bold">Quantity:</label>
            <input type="number" min="0.01" step="any" name="single_added_qty" class="form-control fw-bold" placeholder="0" required>
        </div>
        <div class="col-md-2">
            <label class="form-label font-weight-bold">Action:</label>
            <select name="stock_mode" class="form-select">
                <option value="add" selected>+ Add to Current Stock</option>
                <option value="set">= Set Exact Total Stock</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label font-weight-bold">Notes / Reference:</label>
            <input type="text" name="single_notes" class="form-control" value="Fresh Bake Entry" placeholder="Notes">
        </div>
        <div class="col-md-2">
            <button type="submit" name="submit_single_product_stock" value="1" class="btn btn-primary w-100 fw-bold py-2">
                <i class="fa-solid fa-check me-1"></i> Add Stock Now
            </button>
        </div>
    </form>
</div>

<!-- SECTION 2: PRODUCT LIST WITH INDIVIDUAL ROW STOCK ACTION -->
<div class="card card-bakery p-4 shadow-sm border-0">
    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
        <div>
            <h5 class="fw-bold mb-1"><i class="fa-solid fa-list-check text-warning me-2"></i> All Bakery Items Stock List</h5>
            <small class="text-muted">Type quantity next to any item and click "+ Add Stock" for instant individual update</small>
        </div>
        <div style="width: 300px;">
            <input type="text" id="productSearchInput" class="form-control form-control-sm" placeholder="🔍 Search item name or SKU...">
        </div>
    </div>

    <form method="POST" action="">
        <div class="row align-items-center mb-3">
            <div class="col-md-8">
                <label class="form-label font-weight-bold text-muted small mb-0">Batch Reference Note (for row updates):</label>
                <input type="text" name="batch_notes" class="form-control form-control-sm" value="Daily Baking Stock Entry - <?php echo date('d M Y'); ?>">
            </div>
            <div class="col-md-4 text-end">
                <button type="submit" name="submit_daily_stock" value="1" class="btn btn-outline-primary btn-sm fw-bold">
                    <i class="fa-solid fa-save me-1"></i> Save All Entered Quantities (Bulk)
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="stockTable">
                <thead class="table-light">
                    <tr>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Product Name</th>
                        <th class="text-center">Current Stock</th>
                        <th class="text-center" style="width: 180px;">+ Add Stock Qty</th>
                        <th class="text-center" style="width: 140px;">Individual Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No active products found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr class="product-row">
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($p['sku']); ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($p['category_name']); ?></span></td>
                                <td class="product-name">
                                    <strong class="text-dark d-block"><?php echo htmlspecialchars($p['name']); ?></strong>
                                </td>
                                <td class="text-center">
                                    <?php if ($p['current_stock'] <= $p['min_stock_alert']): ?>
                                        <span class="badge bg-danger px-3 py-2 fs-6"><?php echo number_format($p['current_stock'], 0) . ' ' . htmlspecialchars($p['unit']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success px-3 py-2 fs-6"><?php echo number_format($p['current_stock'], 0) . ' ' . htmlspecialchars($p['unit']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light">+</span>
                                        <input type="number" min="0" step="any" name="row_added_stock[<?php echo $p['id']; ?>]" class="form-control text-center fw-bold fs-6" placeholder="0">
                                        <input type="hidden" name="added_stock[<?php echo $p['id']; ?>]" class="bulk-qty-mirror">
                                        <span class="input-group-text bg-light"><?php echo htmlspecialchars($p['unit']); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="submit" name="submit_row_stock" value="<?php echo $p['id']; ?>" class="btn btn-sm btn-success fw-bold px-3">
                                        <i class="fa-solid fa-plus me-1"></i> Add Stock
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-end mt-4 pt-3 border-top">
            <button type="submit" name="submit_daily_stock" value="1" class="btn btn-primary px-5 py-2 fw-bold">
                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Save All Entered Quantities (Bulk)
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search Filter
    const searchInput = document.getElementById('productSearchInput');
    const tableRows = document.querySelectorAll('.product-row');

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase().trim();
            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Mirror row_added_stock to added_stock for bulk submission
    const inputs = document.querySelectorAll('input[name^="row_added_stock"]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const parentTd = this.closest('td');
            const bulkMirror = parentTd.querySelector('.bulk-qty-mirror');
            if (bulkMirror) {
                bulkMirror.value = this.value;
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
