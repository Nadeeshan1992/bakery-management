<?php
// modules/products/index.php - Product Catalog Management & Quick Individual Stock Entry
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireRole(['admin', 'owner', 'pos_operator']);

$db = getDB();

// Handle Quick Individual Stock Entry POST from Catalog Page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quick_stock'])) {
    $productId = intval($_POST['product_id'] ?? 0);
    $addedQty = floatval($_POST['added_qty'] ?? 0);
    $notes = trim($_POST['notes'] ?? 'Individual item stock entry');

    if ($productId > 0 && $addedQty > 0) {
        $stmtP = $db->prepare("SELECT name, current_stock, unit FROM products WHERE id = ?");
        $stmtP->execute([$productId]);
        $prod = $stmtP->fetch();

        if ($prod) {
            $stmtUpdate = $db->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
            $stmtUpdate->execute([$addedQty, $productId]);
            
            $stmtLog = $db->prepare("
                INSERT INTO product_stock_logs (product_id, log_type, quantity, notes, created_by) 
                VALUES (?, 'manual_adjustment', ?, ?, ?)
            ");
            $stmtLog->execute([$productId, $addedQty, $notes, getCurrentUserId()]);

            $newStock = (float)$prod['current_stock'] + $addedQty;
            setFlash('success', "Added +" . number_format($addedQty, 0) . " {$prod['unit']} to " . $prod['name'] . "! (New Total: " . number_format($newStock, 0) . " {$prod['unit']})");
        }
    } else {
        setFlash('error', 'Please enter a valid stock quantity.');
    }

    header('Location: ' . BASE_URL . 'modules/products/index.php');
    exit;
}

$stmt = $db->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$products = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Bakery Product Catalog</h4>
        <p class="text-muted mb-0">Manage baked items, finished stock levels, retail & wholesale pricing, and category classifications</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>modules/products/daily_stock.php" class="btn btn-outline-primary">
            <i class="fa-solid fa-boxes-stacked me-1"></i> Daily Stock Entry
        </a>
        <a href="<?php echo BASE_URL; ?>modules/products/categories.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-tags me-1"></i> Categories
        </a>
        <a href="<?php echo BASE_URL; ?>modules/products/add.php" class="btn btn-warning text-dark fw-bold">
            <i class="fa-solid fa-plus me-1"></i> Add New Product
        </a>
    </div>
</div>

<div class="card card-bakery p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>SKU Code</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th class="text-center">Current Stock</th>
                    <th>Retail Price</th>
                    <th>Wholesale Price</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No products found. Click 'Add New Product' to get started.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($p['sku']); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($p['category_name']); ?></span></td>
                            <td class="text-center">
                                <?php if ($p['current_stock'] <= 0): ?>
                                    <span class="badge bg-danger px-2 py-1">0 <?php echo htmlspecialchars($p['unit']); ?> (Out of Stock)</span>
                                <?php elseif ($p['current_stock'] <= $p['min_stock_alert']): ?>
                                    <span class="badge bg-warning text-dark px-2 py-1"><?php echo number_format($p['current_stock'], 0) . ' ' . htmlspecialchars($p['unit']); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success px-2 py-1"><?php echo number_format($p['current_stock'], 0) . ' ' . htmlspecialchars($p['unit']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong class="text-success"><?php echo formatMoney($p['price_retail'] > 0 ? $p['price_retail'] : $p['price']); ?></strong></td>
                            <td><strong class="text-primary"><?php echo formatMoney($p['price_wholesale']); ?></strong></td>
                            <td><span class="text-muted text-lowercase"><?php echo htmlspecialchars($p['unit']); ?></span></td>
                            <td><?php echo getStatusBadge($p['status']); ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#quickStockModal<?php echo $p['id']; ?>">
                                    <i class="fa-solid fa-plus me-1"></i> Stock
                                </button>
                                <a href="<?php echo BASE_URL; ?>modules/products/edit.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-light border text-primary">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <a href="<?php echo BASE_URL; ?>modules/products/delete.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('Are you sure you want to delete this product?');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <!-- Quick Stock Entry Modal for Product #<?php echo $p['id']; ?> -->
                        <div class="modal fade text-start" id="quickStockModal<?php echo $p['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="">
                                        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold text-dark">
                                                <i class="fa-solid fa-boxes-stacked text-warning me-2"></i> Add Stock: <?php echo htmlspecialchars($p['name']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small mb-1">Current Stock Level:</label>
                                                <div class="fs-5 fw-bold text-dark"><?php echo number_format($p['current_stock'], 0) . ' ' . htmlspecialchars($p['unit']); ?></div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label font-weight-bold">+ Add Stock Quantity (<?php echo htmlspecialchars($p['unit']); ?>):</label>
                                                <input type="number" min="0.01" step="any" name="added_qty" class="form-control form-control-lg fw-bold" placeholder="0" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label font-weight-bold">Notes / Reference:</label>
                                                <input type="text" name="notes" class="form-control" value="Individual stock entry">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="submit_quick_stock" value="1" class="btn btn-success fw-bold">
                                                <i class="fa-solid fa-check me-1"></i> Update Item Stock
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
