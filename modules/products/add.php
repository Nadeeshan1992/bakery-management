<?php
// modules/products/add.php - Add New Product Form
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $priceRetail = floatval($_POST['price_retail'] ?? 0);
    $priceWholesale = floatval($_POST['price_wholesale'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'pcs');

    if (empty($name) || empty($sku) || $categoryId <= 0 || $priceRetail < 0) {
        setFlash('error', 'Please fill in Product Name, SKU, Category, and Selling Price (Retail).');
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO products (category_id, sku, name, price_retail, price_wholesale, price, unit, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$categoryId, $sku, $name, $priceRetail, $priceWholesale, $priceRetail, $unit]);
            setFlash('success', 'Product "' . htmlspecialchars($name) . '" created successfully!');
            header('Location: ' . BASE_URL . 'modules/products/index.php');
            exit;
        } catch (Exception $e) {
            setFlash('error', 'Error creating product: ' . $e->getMessage());
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
                <h4 class="fw-bold mb-0">Add New Bakery Product</h4>
                <a href="<?php echo BASE_URL; ?>modules/products/index.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>

            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label font-weight-bold">Product Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Chocolate Truffle Cake (1kg)" required autofocus>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">SKU Code *</label>
                        <input type="text" name="sku" class="form-control" placeholder="e.g. CHO-TRU-01" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Category *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Selling Price Retail (Rs.) *</label>
                        <input type="number" step="0.01" min="0" name="price_retail" class="form-control" placeholder="0.00" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Selling Price Wholesale (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="price_wholesale" class="form-control" placeholder="0.00">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Unit Type</label>
                        <select name="unit" class="form-select">
                            <option value="pcs">pcs (Pieces)</option>
                            <option value="slice">slice</option>
                            <option value="pack">pack</option>
                            <option value="kg">kg</option>
                            <option value="cup">cup</option>
                        </select>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="submit" class="btn btn-warning text-dark fw-bold px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Product
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
