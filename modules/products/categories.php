<?php
// modules/products/categories.php - Category Management
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!empty($name)) {
            try {
                $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                setFlash('success', 'Category "' . htmlspecialchars($name) . '" created successfully.');
            } catch (Exception $e) {
                setFlash('error', 'Error adding category: ' . $e->getMessage());
            }
        }
    } elseif (isset($_POST['delete_category'])) {
        $catId = (int)$_POST['category_id'];
        try {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$catId]);
            setFlash('success', 'Category deleted successfully.');
        } catch (Exception $e) {
            setFlash('error', 'Cannot delete category in use by products.');
        }
    }
}

$categories = $db->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name ASC")->fetchAll();
?>

<div class="row g-4">
    <!-- Category Form -->
    <div class="col-md-5">
        <div class="card card-bakery p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus-circle text-warning me-2"></i> Add New Category</h5>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Category Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Cupcakes & Muffins" required>
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief category summary..."></textarea>
                </div>
                <button type="submit" name="add_category" value="1" class="btn btn-warning text-dark fw-bold w-100">
                    <i class="fa-solid fa-save me-1"></i> Save Category
                </button>
            </form>
        </div>
    </div>

    <!-- Category List -->
    <div class="col-md-7">
        <div class="card card-bakery p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-tags text-warning me-2"></i> Existing Categories</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-center">Products</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td><span class="text-muted text-xs"><?php echo htmlspecialchars($cat['description'] ?? 'N/A'); ?></span></td>
                                <td class="text-center"><span class="badge bg-secondary"><?php echo $cat['product_count']; ?></span></td>
                                <td class="text-end">
                                    <form method="POST" action="" onsubmit="return confirm('Delete this category?');" class="d-inline">
                                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                        <button type="submit" name="delete_category" value="1" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
