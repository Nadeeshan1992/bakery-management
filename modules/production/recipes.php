<?php
// modules/production/recipes.php - Manage Product Recipes (Bill of Materials)
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_recipe_item'])) {
        $productId = intval($_POST['product_id'] ?? 0);
        $ingredientId = intval($_POST['ingredient_id'] ?? 0);
        $quantity = floatval($_POST['quantity'] ?? 0);

        if ($productId > 0 && $ingredientId > 0 && $quantity > 0) {
            try {
                $stmt = $db->prepare("INSERT INTO recipes (product_id, ingredient_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$productId, $ingredientId, $quantity]);
                setFlash('success', 'Ingredient added to product recipe.');
            } catch (Exception $e) {
                setFlash('error', 'Error adding recipe item: ' . $e->getMessage());
            }
        }
    } elseif (isset($_POST['delete_recipe_item'])) {
        $recipeId = intval($_POST['recipe_id']);
        try {
            $stmt = $db->prepare("DELETE FROM recipes WHERE id = ?");
            $stmt->execute([$recipeId]);
            setFlash('success', 'Recipe item removed.');
        } catch (Exception $e) {
            setFlash('error', 'Delete failed.');
        }
    }
}

$products = $db->query("SELECT * FROM products WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$ingredients = $db->query("SELECT * FROM ingredients ORDER BY name ASC")->fetchAll();

$selectedProdId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : ($products[0]['id'] ?? 0);

$currentRecipe = [];
if ($selectedProdId > 0) {
    $stmt = $db->prepare("SELECT r.*, i.name as ingredient_name, i.unit FROM recipes r JOIN ingredients i ON r.ingredient_id = i.id WHERE r.product_id = ?");
    $stmt->execute([$selectedProdId]);
    $currentRecipe = $stmt->fetchAll();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Master Product Recipes (Bill of Materials)</h4>
        <p class="text-muted mb-0">Configure raw ingredient usage required for baking 1 unit of product</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/production/index.php" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Production
    </a>
</div>

<div class="row g-4">
    <!-- Select Product & Add Recipe Item -->
    <div class="col-md-5">
        <div class="card card-bakery p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-cookie text-warning me-2"></i> Select Product</h5>
            <form method="GET" action="">
                <select name="product_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $selectedProdId) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="card card-bakery p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus-circle text-warning me-2"></i> Add Ingredient to Recipe</h5>
            <form method="POST" action="">
                <input type="hidden" name="product_id" value="<?php echo $selectedProdId; ?>">
                
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Ingredient *</label>
                    <select name="ingredient_id" class="form-select" required>
                        <option value="">-- Select Ingredient --</option>
                        <?php foreach ($ingredients as $ing): ?>
                            <option value="<?php echo $ing['id']; ?>"><?php echo htmlspecialchars($ing['name']); ?> (in <?php echo $ing['unit']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold">Quantity Required per 1 Unit *</label>
                    <input type="number" step="0.01" min="0.001" name="quantity" class="form-control" placeholder="e.g. 0.25" required>
                </div>

                <button type="submit" name="add_recipe_item" value="1" class="btn btn-warning text-dark fw-bold w-100">
                    <i class="fa-solid fa-plus me-1"></i> Add to Recipe
                </button>
            </form>
        </div>
    </div>

    <!-- Current Recipe Ingredient Breakdown -->
    <div class="col-md-7">
        <div class="card card-bakery p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-list text-warning me-2"></i> Ingredients Required per Unit</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Ingredient</th>
                            <th>Qty Needed (per 1 unit)</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($currentRecipe)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">No recipe ingredients defined for this product yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($currentRecipe as $r): ?>
                                <tr>
                                    <td><strong class="text-dark"><?php echo htmlspecialchars($r['ingredient_name']); ?></strong></td>
                                    <td><span class="badge bg-light text-dark border fs-6"><?php echo $r['quantity'] . ' ' . $r['unit']; ?></span></td>
                                    <td class="text-end">
                                        <form method="POST" action="" onsubmit="return confirm('Remove ingredient from recipe?');" class="d-inline">
                                            <input type="hidden" name="recipe_id" value="<?php echo $r['id']; ?>">
                                            <button type="submit" name="delete_recipe_item" value="1" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
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
