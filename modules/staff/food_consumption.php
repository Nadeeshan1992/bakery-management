<?php
// modules/staff/food_consumption.php - Staff Food Items Consumption Entry, Edit, Delete & Log
require_once __DIR__ . '/../../includes/header.php';

requireRole(['admin', 'owner', 'sales_person']);

$db = getDB();
$message = '';
$error = '';

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    try {
        $stmtEntry = $db->prepare("SELECT * FROM staff_food_consumption WHERE id = ?");
        $stmtEntry->execute([$deleteId]);
        $entry = $stmtEntry->fetch();

        if ($entry) {
            $db->beginTransaction();

            // Restore product stock
            $stmtRestock = $db->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
            $stmtRestock->execute([$entry['quantity'], $entry['product_id']]);

            // Delete entry record
            $stmtDel = $db->prepare("DELETE FROM staff_food_consumption WHERE id = ?");
            $stmtDel->execute([$deleteId]);

            $db->commit();
            setFlash('success', "Food consumption entry deleted. Restored " . number_format($entry['quantity'], 1) . " pcs of '" . $entry['product_name'] . "' back to inventory stock.");
        } else {
            setFlash('danger', "Record not found.");
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        setFlash('danger', "Error deleting entry: " . $e->getMessage());
    }
    $monthParam = $_GET['month'] ?? date('Y-m');
    header('Location: ' . BASE_URL . 'modules/staff/food_consumption.php?month=' . urlencode($monthParam));
    exit;
}

// Fetch record for Editing if edit_id is provided
$editingEntry = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $stmtE = $db->prepare("SELECT * FROM staff_food_consumption WHERE id = ?");
    $stmtE->execute([$editId]);
    $editingEntry = $stmtE->fetch();
}

// Fetch Active Staff Members
$stmtStaff = $db->query("SELECT id, emp_number, name, designation FROM staff WHERE status = 'active' ORDER BY emp_number ASC");
$staffMembers = $stmtStaff->fetchAll();

// Fetch Active Bakery Products
$stmtProducts = $db->query("SELECT id, name, price, current_stock, unit FROM products WHERE status = 'active' ORDER BY name ASC");
$products = $stmtProducts->fetchAll();

// Process Food Entry Submission (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId          = (int)($_POST['edit_id'] ?? 0);
    $staffId         = (int)($_POST['staff_id'] ?? 0);
    $productId       = (int)($_POST['product_id'] ?? 0);
    $quantity        = (float)($_POST['quantity'] ?? 1);
    $unitPrice       = (float)($_POST['unit_price'] ?? 0);
    $consumptionDate = trim($_POST['consumption_date'] ?? date('Y-m-d'));
    $notes           = trim($_POST['notes'] ?? '');

    if ($staffId <= 0 || $productId <= 0 || $quantity <= 0) {
        $error = "Please select a valid staff member, product, and quantity.";
    } else {
        try {
            // Get selected product info
            $stmtP = $db->prepare("SELECT name, price, current_stock FROM products WHERE id = ?");
            $stmtP->execute([$productId]);
            $prod = $stmtP->fetch();

            if (!$prod) {
                $error = "Selected bakery product not found.";
            } else {
                if ($unitPrice <= 0) {
                    $unitPrice = (float)$prod['price'];
                }
                $totalPrice = $quantity * $unitPrice;
                $userId = getCurrentUserId();

                $db->beginTransaction();

                if ($editId > 0) {
                    // Updating Existing Record
                    $stmtOld = $db->prepare("SELECT * FROM staff_food_consumption WHERE id = ?");
                    $stmtOld->execute([$editId]);
                    $old = $stmtOld->fetch();

                    if ($old) {
                        // Restore old stock
                        $stmtRestore = $db->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
                        $stmtRestore->execute([$old['quantity'], $old['product_id']]);

                        // Check availability for new quantity
                        $stmtP->execute([$productId]);
                        $freshProd = $stmtP->fetch();

                        if ($freshProd['current_stock'] < $quantity) {
                            $db->rollBack();
                            $error = "Cannot update! Remaining stock for '" . $freshProd['name'] . "' is only " . $freshProd['current_stock'] . " pcs.";
                        } else {
                            // Update entry
                            $stmtUpd = $db->prepare("
                                UPDATE staff_food_consumption 
                                SET staff_id = ?, product_id = ?, product_name = ?, quantity = ?, unit_price = ?, total_price = ?, consumption_date = ?, notes = ?
                                WHERE id = ?
                            ");
                            $stmtUpd->execute([
                                $staffId, $productId, $prod['name'], $quantity, $unitPrice, $totalPrice, $consumptionDate, $notes, $editId
                            ]);

                            // Deduct new stock
                            $stmtDeduct = $db->prepare("UPDATE products SET current_stock = current_stock - ? WHERE id = ?");
                            $stmtDeduct->execute([$quantity, $productId]);

                            $db->commit();
                            setFlash('success', "Food consumption entry updated successfully! Inventory stock and paysheet deductions adjusted.");
                            header('Location: ' . BASE_URL . 'modules/staff/food_consumption.php?month=' . urlencode(date('Y-m', strtotime($consumptionDate))));
                            exit;
                        }
                    }
                } else {
                    // Creating New Record
                    if ($prod['current_stock'] < $quantity) {
                        $error = "Insufficient stock! Remaining stock for '" . $prod['name'] . "' is only " . $prod['current_stock'] . " pcs.";
                    } else {
                        // 1. Insert Food Consumption Log
                        $stmtInsert = $db->prepare("
                            INSERT INTO staff_food_consumption (staff_id, product_id, product_name, quantity, unit_price, total_price, consumption_date, notes, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmtInsert->execute([
                            $staffId, $productId, $prod['name'], $quantity, $unitPrice, $totalPrice, $consumptionDate, $notes, $userId
                        ]);

                        // 2. Deduct product stock
                        $stmtStock = $db->prepare("UPDATE products SET current_stock = current_stock - ? WHERE id = ?");
                        $stmtStock->execute([$quantity, $productId]);

                        $db->commit();
                        setFlash('success', "Food item '" . $prod['name'] . "' (x$quantity) logged for staff member. Stock updated & added to monthly paysheet!");
                        header('Location: ' . BASE_URL . 'modules/staff/food_consumption.php?month=' . urlencode(date('Y-m', strtotime($consumptionDate))));
                        exit;
                    }
                }
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch Food Consumption History
$selectedMonth = $_GET['month'] ?? date('Y-m');
$filterStaff = (int)($_GET['staff_id'] ?? 0);

$query = "
    SELECT f.*, s.emp_number, s.name as staff_name, s.designation, u.full_name as created_by_name 
    FROM staff_food_consumption f 
    JOIN staff s ON f.staff_id = s.id 
    LEFT JOIN users u ON f.created_by = u.id 
    WHERE DATE_FORMAT(f.consumption_date, '%Y-%m') = ?
";
$params = [$selectedMonth];
if ($filterStaff > 0) {
    $query .= " AND f.staff_id = ?";
    $params[] = $filterStaff;
}
$query .= " ORDER BY f.consumption_date DESC, f.id DESC";

$stmtLogs = $db->prepare($query);
$stmtLogs->execute($params);
$foodLogs = $stmtLogs->fetchAll();

// Total monthly food consumption cost
$totalMonthlyFood = 0;
foreach ($foodLogs as $fl) {
    $totalMonthlyFood += (float)$fl['total_price'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-utensils text-warning me-2"></i> Staff Food Items Consumption</h4>
        <p class="text-muted small mb-0">Record, edit, or remove bakery food items taken by staff. Stock auto-adjusts and costs update on monthly paysheets.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>modules/payroll/index.php" class="btn btn-warning text-dark font-weight-bold">
            <i class="fa-solid fa-calculator me-1"></i> Go to Salary Payroll
        </a>
        <a href="<?php echo BASE_URL; ?>modules/staff/index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-users me-1"></i> Staff Directory
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4">
        <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<!-- Log / Edit Food Item Form -->
<div class="card card-bakery p-4 mb-4 <?php echo $editingEntry ? 'border-warning' : ''; ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">
            <?php if ($editingEntry): ?>
                <i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Staff Food Item Record #<?php echo $editingEntry['id']; ?>
            <?php else: ?>
                <i class="fa-solid fa-cart-plus text-success me-2"></i> Issue Food Item to Staff
            <?php endif; ?>
        </h5>
        <?php if ($editingEntry): ?>
            <a href="<?php echo BASE_URL; ?>modules/staff/food_consumption.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-xmark me-1"></i> Cancel Edit
            </a>
        <?php endif; ?>
    </div>

    <form method="POST" action="">
        <?php if ($editingEntry): ?>
            <input type="hidden" name="edit_id" value="<?php echo $editingEntry['id']; ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="staff_id" class="form-label fw-bold">Select Staff Member <span class="text-danger">*</span></label>
                <select class="form-select" id="staff_id" name="staff_id" required>
                    <option value="">-- Choose Employee --</option>
                    <?php 
                    $selectedStaff = $editingEntry ? $editingEntry['staff_id'] : '';
                    foreach ($staffMembers as $s): 
                    ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($selectedStaff == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['emp_number'] . ' - ' . $s['name'] . ' (' . $s['designation'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="product_id" class="form-label fw-bold">Select Bakery Item <span class="text-danger">*</span></label>
                <select class="form-select" id="product_id" name="product_id" onchange="updateUnitPrice()" required>
                    <option value="" data-price="0">-- Choose Bakery Item --</option>
                    <?php 
                    $selectedProd = $editingEntry ? $editingEntry['product_id'] : '';
                    foreach ($products as $p): 
                    ?>
                        <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>" <?php echo ($selectedProd == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name'] . ' (Stock: ' . $p['current_stock'] . ' ' . $p['unit'] . ' - Rs.' . $p['price'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="quantity" class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                <input type="number" step="0.5" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($editingEntry ? $editingEntry['quantity'] : '1'); ?>" min="0.5" oninput="calculateTotal()" required>
            </div>

            <div class="col-md-2">
                <label for="unit_price" class="form-label fw-bold">Unit Price (Rs.)</label>
                <input type="number" step="0.01" class="form-control" id="unit_price" name="unit_price" value="<?php echo htmlspecialchars($editingEntry ? $editingEntry['unit_price'] : '0.00'); ?>" oninput="calculateTotal()">
            </div>

            <div class="col-md-3">
                <label for="consumption_date" class="form-label fw-bold">Date</label>
                <input type="date" class="form-control" id="consumption_date" name="consumption_date" value="<?php echo htmlspecialchars($editingEntry ? $editingEntry['consumption_date'] : date('Y-m-d')); ?>" required>
            </div>

            <div class="col-md-6">
                <label for="notes" class="form-label fw-bold">Notes / Reason (Optional)</label>
                <input type="text" class="form-control" id="notes" name="notes" placeholder="e.g. Breakfast bun / Tea time snack" value="<?php echo htmlspecialchars($editingEntry ? $editingEntry['notes'] : ''); ?>">
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <div class="w-100">
                    <small class="text-muted d-block mb-1">Total Food Cost:</small>
                    <h5 class="fw-bold text-danger mb-0" id="total_cost_display">Rs. <?php echo number_format($editingEntry ? $editingEntry['total_price'] : 0, 2); ?></h5>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-end gap-2">
            <?php if ($editingEntry): ?>
                <a href="<?php echo BASE_URL; ?>modules/staff/food_consumption.php" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-warning text-dark font-weight-bold btn-lg">
                    <i class="fa-solid fa-check me-1"></i> Update Food Record & Adjust Stock
                </button>
            <?php else: ?>
                <button type="submit" class="btn btn-warning text-dark font-weight-bold btn-lg">
                    <i class="fa-solid fa-plus-circle me-1"></i> Add Food Consumption & Deduct Stock
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- History Log Table -->
<div class="card card-bakery p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Food Items Taken History</h5>
            <small class="text-muted">Total Food Consumption for period: <strong class="text-danger"><?php echo formatMoney($totalMonthlyFood); ?></strong></small>
        </div>
        <form method="GET" action="" class="d-flex align-items-center gap-2">
            <input type="month" id="month" name="month" class="form-control form-control-sm" value="<?php echo htmlspecialchars($selectedMonth); ?>" onchange="this.form.submit()">
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>EMP Code</th>
                    <th>Staff Member</th>
                    <th>Product Name</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total Deducted</th>
                    <th>Notes</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($foodLogs)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No staff food consumption logged for <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($foodLogs as $log): ?>
                        <tr>
                            <td><strong><?php echo date('M d, Y', strtotime($log['consumption_date'])); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($log['emp_number']); ?></code></td>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($log['staff_name']); ?></strong></td>
                            <td><span class="badge bg-light text-dark border"><i class="fa-solid fa-bread-slice me-1 text-warning"></i> <?php echo htmlspecialchars($log['product_name']); ?></span></td>
                            <td><strong><?php echo number_format($log['quantity'], 1); ?> pcs</strong></td>
                            <td><?php echo formatMoney($log['unit_price']); ?></td>
                            <td><strong class="text-danger"><?php echo formatMoney($log['total_price']); ?></strong></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($log['notes'] ?: '-'); ?></small></td>
                            <td class="text-end text-nowrap">
                                <a href="<?php echo BASE_URL; ?>modules/staff/food_consumption.php?edit_id=<?php echo $log['id']; ?>&month=<?php echo urlencode($selectedMonth); ?>" class="btn btn-sm btn-outline-secondary me-1">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="triggerDeleteModal(<?php echo $log['id']; ?>, '<?php echo htmlspecialchars(addslashes($log['product_name'])); ?>', <?php echo $log['quantity']; ?>, '<?php echo htmlspecialchars(addslashes($log['staff_name'])); ?>', '<?php echo urlencode($selectedMonth); ?>')">
                                    <i class="fa-solid fa-trash-can"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="deleteModalLabel"><i class="fa-solid fa-triangle-exclamation me-2"></i> Confirm Food Record Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="text-danger mb-3">
                    <i class="fa-solid fa-trash-can fa-3x"></i>
                </div>
                <h6 class="fw-bold mb-2">Are you sure you want to delete this food record?</h6>
                <p class="text-muted small mb-3">
                    Product: <strong id="modal_product_name" class="text-dark"></strong><br>
                    Issued To: <strong id="modal_staff_name" class="text-dark"></strong><br>
                    Quantity to Restore: <strong id="modal_quantity" class="text-success fs-6"></strong> pcs
                </p>
                <div class="alert alert-warning py-2 text-xs mb-0">
                    <i class="fa-solid fa-circle-info me-1"></i> Deleting this record will <strong>restore <span id="modal_qty_text"></span> pcs back to bakery inventory stock</strong> and remove the cost deduction from the staff member's monthly paysheet.
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-center">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                <a id="modal_confirm_delete_btn" href="#" class="btn btn-danger font-weight-bold px-4">
                    <i class="fa-solid fa-trash-can me-1"></i> Yes, Delete & Restore Stock
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function triggerDeleteModal(id, productName, quantity, staffName, month) {
    document.getElementById('modal_product_name').innerText = productName;
    document.getElementById('modal_staff_name').innerText = staffName;
    document.getElementById('modal_quantity').innerText = quantity + ' pcs';
    document.getElementById('modal_qty_text').innerText = quantity;
    document.getElementById('modal_confirm_delete_btn').href = '<?php echo BASE_URL; ?>modules/staff/food_consumption.php?action=delete&id=' + id + '&month=' + month;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

function updateUnitPrice() {
    const select = document.getElementById('product_id');
    const selectedOption = select.options[select.selectedIndex];
    const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
    document.getElementById('unit_price').value = price.toFixed(2);
    calculateTotal();
}

function calculateTotal() {
    const qty = parseFloat(document.getElementById('quantity').value) || 0;
    const price = parseFloat(document.getElementById('unit_price').value) || 0;
    const total = qty * price;
    document.getElementById('total_cost_display').innerText = 'Rs. ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
