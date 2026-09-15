<?php
// modules/products/edit.php - Edit Product Form
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    header('Location: ' . BASE_URL . 'modules/products/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $priceRetail = floatval($_POST['price_retail'] ?? 0);
    $priceWholesale = floatval($_POST['price_wholesale'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'pcs');
    $status = $_POST['status'] ?? 'active';

    if (empty($name) || empty($sku) || $categoryId <= 0 || $priceRetail < 0) {
        setFlash('error', 'Please fill in all required fields accurately.');
    } else {
        try {
            $stmtUpdate = $db->prepare("UPDATE products SET category_id = ?, sku = ?, name = ?, price_retail = ?, price_wholesale = ?, price = ?, unit = ?, status = ? WHERE id = ?");
            $stmtUpdate->execute([$categoryId, $sku, $name, $priceRetail, $priceWholesale, $priceRetail, $unit, $status, $id]);
            setFlash('success', 'Product updated successfully!');
            header('Location: ' . BASE_URL . 'modules/products/index.php');
            exit;
        } catch (Exception $e) {
            setFlash('error', 'Error updating product: ' . $e->getMessage());
        }
    }
}

$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-bakery p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Edit Product: <?php echo htmlspecialchars($product['name']); ?></h4>
                <a href="<?php echo BASE_URL; ?>modules/products/index.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>

            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label font-weight-bold">Product Name *</label>
                        <input type="text" name="name" id="productName" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">SKU Code *</label>
                        <input type="text" name="sku" id="productSku" class="form-control bg-light" value="<?php echo htmlspecialchars($product['sku']); ?>" readonly required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Category *</label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Selling Price Retail (Rs.) *</label>
                        <input type="number" step="0.01" min="0" name="price_retail" class="form-control" value="<?php echo $product['price_retail'] > 0 ? $product['price_retail'] : $product['price']; ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Selling Price Wholesale (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="price_wholesale" class="form-control" value="<?php echo $product['price_wholesale']; ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Unit Type</label>
                        <select name="unit" class="form-select">
                            <option value="pcs" <?php echo ($product['unit'] == 'pcs') ? 'selected' : ''; ?>>pcs</option>
                            <option value="slice" <?php echo ($product['unit'] == 'slice') ? 'selected' : ''; ?>>slice</option>
                            <option value="pack" <?php echo ($product['unit'] == 'pack') ? 'selected' : ''; ?>>pack</option>
                            <option value="kg" <?php echo ($product['unit'] == 'kg') ? 'selected' : ''; ?>>kg</option>
                            <option value="cup" <?php echo ($product['unit'] == 'cup') ? 'selected' : ''; ?>>cup</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo ($product['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="submit" class="btn btn-warning text-dark fw-bold px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Update Product
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
