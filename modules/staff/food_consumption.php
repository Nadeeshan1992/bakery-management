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

// Fetch Active Bakery Products with Retail Price
$stmtProducts = $db->query("
    SELECT id, name, price_retail, price, 
           COALESCE(NULLIF(price_retail, 0), price) as effective_retail_price, 
           current_stock, unit 
    FROM products 
    WHERE status = 'active' 
    ORDER BY name ASC
");
$products = $stmtProducts->fetchAll();

// Process Food Entry Submission (Create Multiple or Update Single)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId = (int)($_POST['edit_id'] ?? 0);

    if ($editId > 0) {
        // --- 1. EDIT EXISTING SINGLE RECORD ---
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
                $stmtP = $db->prepare("SELECT name, price_retail, price, current_stock FROM products WHERE id = ?");
                $stmtP->execute([$productId]);
                $prod = $stmtP->fetch();

                if (!$prod) {
                    $error = "Selected bakery product not found.";
                } else {
                    $retPrice = ($prod['price_retail'] > 0) ? (float)$prod['price_retail'] : (float)$prod['price'];
                    if ($unitPrice <= 0) {
                        $unitPrice = $retPrice;
                    }
                    $totalPrice = $quantity * $unitPrice;

                    $db->beginTransaction();
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
                            $stmtUpd = $db->prepare("
                                UPDATE staff_food_consumption 
                                SET staff_id = ?, product_id = ?, product_name = ?, quantity = ?, unit_price = ?, total_price = ?, consumption_date = ?, notes = ?
                                WHERE id = ?
                            ");
                            $stmtUpd->execute([
                                $staffId, $productId, $prod['name'], $quantity, $unitPrice, $totalPrice, $consumptionDate, $notes, $editId
                            ]);

                            $stmtDeduct = $db->prepare("UPDATE products SET current_stock = current_stock - ? WHERE id = ?");
                            $stmtDeduct->execute([$quantity, $productId]);

                            $db->commit();
                            setFlash('success', "Food consumption entry updated successfully! Inventory stock and paysheet deductions adjusted.");
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
    } else {
        // --- 2. MULTIPLE ITEMS CREATION ---
        $staffId         = (int)($_POST['staff_id'] ?? 0);
        $consumptionDate = trim($_POST['consumption_date'] ?? date('Y-m-d'));
        $generalNotes    = trim($_POST['general_notes'] ?? '');
        $rawItems        = $_POST['items'] ?? [];

        if ($staffId <= 0) {
            $error = "Please select a valid staff member.";
        } elseif (empty($rawItems) || !is_array($rawItems)) {
            $error = "Please add at least one bakery food item.";
        } else {
            // Filter and prepare valid items
            $validItems = [];
            foreach ($rawItems as $rItem) {
                $pId = (int)($rItem['product_id'] ?? 0);
                $qty = (float)($rItem['quantity'] ?? 0);
                $uPrice = (float)($rItem['unit_price'] ?? 0);
                $itemNotes = trim($rItem['notes'] ?? $generalNotes);

                if ($pId > 0 && $qty > 0) {
                    $validItems[] = [
                        'product_id' => $pId,
                        'quantity'   => $qty,
                        'unit_price' => $uPrice,
                        'notes'      => $itemNotes
                    ];
                }
            }

            if (empty($validItems)) {
                $error = "Please select bakery items and enter valid quantities (> 0).";
            } else {
                try {
                    $db->beginTransaction();
                    $stmtProd = $db->prepare("SELECT id, name, price_retail, price, current_stock FROM products WHERE id = ?");

                    $preparedItems = [];
                    foreach ($validItems as $vi) {
                        $stmtProd->execute([$vi['product_id']]);
                        $pData = $stmtProd->fetch();

                        if (!$pData) {
                            throw new Exception("Product ID #" . $vi['product_id'] . " not found in database.");
                        }

                        if ($pData['current_stock'] < $vi['quantity']) {
                            throw new Exception("Insufficient stock for '" . $pData['name'] . "'! Remaining stock is only " . $pData['current_stock'] . " pcs, but requested " . $vi['quantity'] . " pcs.");
                        }

                        $effectiveRetailPrice = ($pData['price_retail'] > 0) ? (float)$pData['price_retail'] : (float)$pData['price'];
                        $finalUnitPrice = ($vi['unit_price'] > 0) ? $vi['unit_price'] : $effectiveRetailPrice;
                        $finalTotalPrice = $vi['quantity'] * $finalUnitPrice;

                        $preparedItems[] = [
                            'product_id'   => $pData['id'],
                            'product_name' => $pData['name'],
                            'quantity'     => $vi['quantity'],
                            'unit_price'   => $finalUnitPrice,
                            'total_price'  => $finalTotalPrice,
                            'notes'        => $vi['notes']
                        ];
                    }

                    // Generate unique Bill Number: SFC-YYYYMMDD-XXX
                    $datePrefix = date('Ymd', strtotime($consumptionDate));
                    $stmtCount = $db->query("SELECT COUNT(DISTINCT bill_no) FROM staff_food_consumption WHERE bill_no LIKE 'SFC-$datePrefix-%'");
                    $dayCount = (int)$stmtCount->fetchColumn() + 1;
                    $billNo = 'SFC-' . $datePrefix . '-' . str_pad($dayCount, 3, '0', STR_PAD_LEFT);

                    $userId = getCurrentUserId();
                    $stmtInsert = $db->prepare("
                        INSERT INTO staff_food_consumption (bill_no, staff_id, product_id, product_name, quantity, unit_price, total_price, consumption_date, notes, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtDeduct = $db->prepare("UPDATE products SET current_stock = current_stock - ? WHERE id = ?");

                    $totalBillAmount = 0;
                    foreach ($preparedItems as $pi) {
                        $stmtInsert->execute([
                            $billNo,
                            $staffId,
                            $pi['product_id'],
                            $pi['product_name'],
                            $pi['quantity'],
                            $pi['unit_price'],
                            $pi['total_price'],
                            $consumptionDate,
                            $pi['notes'],
                            $userId
                        ]);

                        $stmtDeduct->execute([$pi['quantity'], $pi['product_id']]);
                        $totalBillAmount += $pi['total_price'];
                    }

                    $db->commit();

                    $billUrl = BASE_URL . "modules/staff/food_bill.php?bill_no=" . urlencode($billNo);
                    setFlash('success', "Food items successfully issued! <strong>Bill #" . htmlspecialchars($billNo) . "</strong> (Rs. " . number_format($totalBillAmount, 2) . ") created. <a href='" . $billUrl . "' target='_blank' class='btn btn-sm btn-dark text-warning fw-bold ms-2 shadow-sm'><i class='fa-solid fa-print me-1'></i> Print Bill Now</a>");
                    header('Location: ' . BASE_URL . 'modules/staff/food_consumption.php?last_bill=' . urlencode($billNo) . '&month=' . urlencode(date('Y-m', strtotime($consumptionDate))));
                    exit;

                } catch (Exception $e) {
                    if ($db->inTransaction()) $db->rollBack();
                    $error = $e->getMessage();
                }
            }
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
        <p class="text-muted small mb-0">Record single or multiple bakery items issued to staff with retail price loading, printable bills & automatic paysheet deductions.</p>
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

<?php if ($lastBill): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center p-3 mb-4 shadow-sm border border-success">
        <div>
            <h6 class="fw-bold mb-1"><i class="fa-solid fa-circle-check text-success me-2"></i> Food Items Issued & Billed Successfully!</h6>
            <p class="mb-0 text-xs">Voucher <strong>#<?php echo htmlspecialchars($lastBill); ?></strong> is saved, stock deducted from inventory, and debited to staff payroll.</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>modules/staff/food_bill.php?bill_no=<?php echo urlencode($lastBill); ?>" target="_blank" class="btn btn-dark text-warning fw-bold px-3">
                <i class="fa-solid fa-print me-1"></i> Print Bill Receipt
            </a>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4">
        <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<?php if ($editingEntry): ?>
    <!-- EDIT EXISTING SINGLE RECORD FORM -->
    <div class="card card-bakery p-4 mb-4 border-warning shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Food Record #<?php echo $editingEntry['id']; ?> 
                <span class="badge bg-dark text-warning ms-2"><?php echo htmlspecialchars($editingEntry['bill_no'] ?: 'SFC-' . str_pad($editingEntry['id'], 5, '0', STR_PAD_LEFT)); ?></span>
            </h5>
            <a href="<?php echo BASE_URL; ?>modules/staff/food_consumption.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-xmark me-1"></i> Cancel Edit
            </a>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="edit_id" value="<?php echo $editingEntry['id']; ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="edit_staff_id" class="form-label fw-bold text-xs">Staff Member <span class="text-danger">*</span></label>
                    <select class="form-select" id="edit_staff_id" name="staff_id" required>
                        <option value="">-- Choose Employee --</option>
                        <?php foreach ($staffMembers as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo ($editingEntry['staff_id'] == $s['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['emp_number'] . ' - ' . $s['name'] . ' (' . $s['designation'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="edit_product_id" class="form-label fw-bold text-xs">Bakery Product <span class="text-danger">*</span></label>
                    <select class="form-select" id="edit_product_id" name="product_id" onchange="updateEditUnitPrice()" required>
                        <option value="" data-retail-price="0">-- Choose Bakery Item --</option>
                        <?php foreach ($products as $p): 
                            $rPrice = (float)$p['effective_retail_price'];
                        ?>
                            <option value="<?php echo $p['id']; ?>" data-retail-price="<?php echo $rPrice; ?>" <?php echo ($editingEntry['product_id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name'] . ' (Stock: ' . $p['current_stock'] . ' ' . $p['unit'] . ' - Retail: Rs.' . number_format($rPrice, 2) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="edit_quantity" class="form-label fw-bold text-xs">Quantity <span class="text-danger">*</span></label>
                    <input type="number" step="0.5" min="0.5" class="form-control" id="edit_quantity" name="quantity" value="<?php echo htmlspecialchars($editingEntry['quantity']); ?>" oninput="calculateEditTotal()" required>
                </div>

                <div class="col-md-2">
                    <label for="edit_unit_price" class="form-label fw-bold text-xs">Retail Unit Price (Rs.)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="edit_unit_price" name="unit_price" value="<?php echo htmlspecialchars($editingEntry['unit_price']); ?>" oninput="calculateEditTotal()" required>
                </div>

                <div class="col-md-3">
                    <label for="edit_consumption_date" class="form-label fw-bold text-xs">Date</label>
                    <input type="date" class="form-control" id="edit_consumption_date" name="consumption_date" value="<?php echo htmlspecialchars($editingEntry['consumption_date']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="edit_notes" class="form-label fw-bold text-xs">Notes / Reason (Optional)</label>
                    <input type="text" class="form-control" id="edit_notes" name="notes" placeholder="e.g. Breakfast bun / Tea time snack" value="<?php echo htmlspecialchars($editingEntry['notes']); ?>">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <div class="w-100 p-2 bg-light rounded border">
                        <small class="text-muted d-block text-xs">Calculated Cost:</small>
                        <h5 class="fw-bold text-danger mb-0" id="edit_total_display">Rs. <?php echo number_format($editingEntry['total_price'], 2); ?></h5>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>modules/staff/food_consumption.php" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-warning text-dark font-weight-bold">
                    <i class="fa-solid fa-check me-1"></i> Update Record & Adjust Stock
                </button>
            </div>
        </form>
    </div>

<?php else: ?>

    <!-- MULTIPLE ITEMS CONSUMPTION ENTRY FORM -->
    <div class="card card-bakery p-4 mb-4 shadow-sm border">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-cart-plus text-success me-2"></i> Issue Food Items to Staff (Multiple Items Supported)
            </h5>
            <span class="badge bg-warning-subtle text-dark border border-warning-subtle text-xs px-2 py-1">
                <i class="fa-solid fa-tag me-1 text-warning"></i> Auto-loads Retail Price
            </span>
        </div>

        <form method="POST" action="" id="multiFoodForm">
            <!-- Staff & Date Selection Header -->
            <div class="row g-3 mb-3 p-3 bg-light rounded border">
                <div class="col-md-5">
                    <label for="staff_id" class="form-label fw-bold text-xs mb-1">Select Staff Member <span class="text-danger">*</span></label>
                    <select class="form-select" id="staff_id" name="staff_id" required>
                        <option value="">-- Choose Employee --</option>
                        <?php foreach ($staffMembers as $s): ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo htmlspecialchars($s['emp_number'] . ' - ' . $s['name'] . ' (' . $s['designation'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="consumption_date" class="form-label fw-bold text-xs mb-1">Consumption Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="consumption_date" name="consumption_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="general_notes" class="form-label fw-bold text-xs mb-1">Voucher Notes (Optional)</label>
                    <input type="text" class="form-control" id="general_notes" name="general_notes" placeholder="e.g. Breakfast, Lunch & Tea">
                </div>
            </div>

            <!-- Dynamic Items Table -->
            <div class="table-responsive mb-2">
                <table class="table table-bordered align-middle mb-0" id="itemsTable">
                    <thead class="table-light text-xs">
                        <tr>
                            <th style="width: 5%;" class="text-center">#</th>
                            <th style="width: 45%;">Bakery Product (Select to Auto-Load Retail Price) <span class="text-danger">*</span></th>
                            <th style="width: 12%;" class="text-center">Stock</th>
                            <th style="width: 13%;">Quantity <span class="text-danger">*</span></th>
                            <th style="width: 13%;">Retail Price (Rs.)</th>
                            <th style="width: 12%;" class="text-end">Subtotal (Rs.)</th>
                            <th style="width: 5%;" class="text-center"><i class="fa-solid fa-trash-can text-muted"></i></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <!-- Default Initial Row -->
                        <tr class="item-row" data-row-idx="0">
                            <td class="text-center text-muted row-num">1</td>
                            <td>
                                <select class="form-select form-select-sm prod-select" name="items[0][product_id]" onchange="onProductSelectChange(this)" required>
                                    <option value="" data-retail-price="0" data-stock="0" data-unit="pcs">-- Choose Bakery Item --</option>
                                    <?php foreach ($products as $p): 
                                        $rPrice = (float)$p['effective_retail_price'];
                                    ?>
                                        <option value="<?php echo $p['id']; ?>" data-retail-price="<?php echo $rPrice; ?>" data-stock="<?php echo $p['current_stock']; ?>" data-unit="<?php echo htmlspecialchars($p['unit']); ?>">
                                            <?php echo htmlspecialchars($p['name'] . ' (Stock: ' . $p['current_stock'] . ' ' . $p['unit'] . ' - Retail: Rs.' . number_format($rPrice, 2) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="text-center stock-cell">
                                <span class="badge bg-secondary-subtle text-secondary border text-xs">--</span>
                            </td>
                            <td>
                                <input type="number" step="0.5" min="0.5" class="form-control form-control-sm qty-input text-center fw-bold" name="items[0][quantity]" value="1" oninput="onItemQtyOrPriceChange(this)" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm price-input text-end fw-semibold" name="items[0][unit_price]" value="0.00" oninput="onItemQtyOrPriceChange(this)" required>
                            </td>
                            <td class="text-end fw-bold text-dark subtotal-cell">
                                Rs. 0.00
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger p-1 px-2 remove-row-btn" onclick="removeItemRow(this)" disabled>
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add Row Button & Grand Totals Summary Bar -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3 shadow-sm" onclick="addItemRow()">
                    <i class="fa-solid fa-plus me-1"></i> Add Another Item
                </button>
                <div class="text-muted text-xs">
                    Tip: You can add multiple breakfast, lunch, or snack items for the same staff member in one bill.
                </div>
            </div>

            <!-- Summary Footnote & Grand Total Banner -->
            <div class="card p-3 bg-light border mb-3">
                <div class="row align-items-center text-sm">
                    <div class="col-md-4">
                        <div class="d-flex gap-3">
                            <div>Total Items: <strong class="text-dark" id="summary_items_count">1</strong></div>
                            <div>Total Quantity: <strong class="text-primary" id="summary_total_qty">1.0 pcs</strong></div>
                        </div>
                    </div>
                    <div class="col-md-8 text-md-end">
                        <span class="text-muted fs-6 me-2">Grand Total Bill to Deduct:</span>
                        <strong class="fs-4 text-danger" id="summary_grand_total">Rs. 0.00</strong>
                    </div>
                </div>
            </div>

            <!-- Submission Actions -->
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-warning text-dark font-weight-bold btn-lg shadow-sm px-4">
                    <i class="fa-solid fa-receipt me-2"></i> Issue Food Items & Deduct Stock
                </button>
            </div>
        </form>
    </div>

<?php endif; ?>

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
                    <th>Bill #</th>
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
                        <td colspan="10" class="text-center text-muted py-4">No staff food consumption logged for <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($foodLogs as $log): ?>
                        <tr>
                            <td><strong><?php echo date('M d, Y', strtotime($log['consumption_date'])); ?></strong></td>
                            <td>
                                <?php if (!empty($log['bill_no'])): ?>
                                    <span class="badge bg-secondary-subtle text-dark border font-monospace"><?php echo htmlspecialchars($log['bill_no']); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border font-monospace">#<?php echo $log['id']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo htmlspecialchars($log['emp_number']); ?></code></td>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($log['staff_name']); ?></strong></td>
                            <td><span class="badge bg-light text-dark border"><i class="fa-solid fa-bread-slice me-1 text-warning"></i> <?php echo htmlspecialchars($log['product_name']); ?></span></td>
                            <td><strong><?php echo number_format($log['quantity'], 1); ?> pcs</strong></td>
                            <td><?php echo formatMoney($log['unit_price']); ?></td>
                            <td><strong class="text-danger"><?php echo formatMoney($log['total_price']); ?></strong></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($log['notes'] ?: '-'); ?></small></td>
                            <td class="text-end text-nowrap">
                                <?php 
                                    $printUrl = BASE_URL . "modules/staff/food_bill.php?" . (!empty($log['bill_no']) ? "bill_no=" . urlencode($log['bill_no']) : "id=" . $log['id']);
                                ?>
                                <a href="<?php echo $printUrl; ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1" title="Print Bill Voucher">
                                    <i class="fa-solid fa-print"></i> Bill
                                </a>
                                <a href="<?php echo BASE_URL; ?>modules/staff/food_consumption.php?edit_id=<?php echo $log['id']; ?>&month=<?php echo urlencode($selectedMonth); ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit Record">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Delete and Restore Stock" onclick="triggerDeleteModal(<?php echo $log['id']; ?>, '<?php echo htmlspecialchars(addslashes($log['product_name'])); ?>', <?php echo $log['quantity']; ?>, '<?php echo htmlspecialchars(addslashes($log['staff_name'])); ?>', '<?php echo urlencode($selectedMonth); ?>')">
                                    <i class="fa-solid fa-trash-can"></i>
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
let rowCounter = 1;

function onProductSelectChange(selectElem) {
    const row = selectElem.closest('tr');
    if (!row) return;
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const retailPrice = parseFloat(selectedOption.getAttribute('data-retail-price')) || 0;
    const stock = parseFloat(selectedOption.getAttribute('data-stock')) || 0;
    const unit = selectedOption.getAttribute('data-unit') || 'pcs';

    // Auto-fill Unit Price with Retail Price
    const priceInput = row.querySelector('.price-input');
    if (priceInput) {
        priceInput.value = retailPrice.toFixed(2);
    }

    // Update Stock Display
    const stockCell = row.querySelector('.stock-cell');
    if (stockCell) {
        if (selectElem.value) {
            const stockColor = stock <= 5 ? 'danger' : (stock <= 15 ? 'warning' : 'success');
            stockCell.innerHTML = `<span class="badge bg-${stockColor}-subtle text-${stockColor} border border-${stockColor}-subtle text-xs">${stock} ${unit}</span>`;
        } else {
            stockCell.innerHTML = '<span class="badge bg-secondary-subtle text-secondary border text-xs">--</span>';
        }
    }

    calculateRowTotal(row);
    calculateGrandSummary();
}

function onItemQtyOrPriceChange(inputElem) {
    const row = inputElem.closest('tr');
    if (row) {
        calculateRowTotal(row);
        calculateGrandSummary();
    }
}

function calculateRowTotal(row) {
    const qtyInput = row.querySelector('.qty-input');
    const priceInput = row.querySelector('.price-input');
    const subtotalCell = row.querySelector('.subtotal-cell');

    const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
    const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
    const subtotal = qty * price;

    if (subtotalCell) {
        subtotalCell.innerText = 'Rs. ' + subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
}

function addItemRow() {
    const tbody = document.getElementById('itemsTableBody');
    if (!tbody) return;

    const firstRow = tbody.querySelector('.item-row');
    if (!firstRow) return;

    const newRow = firstRow.cloneNode(true);
    const newIdx = rowCounter++;
    newRow.setAttribute('data-row-idx', newIdx);

    // Reset inputs
    const select = newRow.querySelector('.prod-select');
    select.name = `items[${newIdx}][product_id]`;
    select.selectedIndex = 0;

    const stockCell = newRow.querySelector('.stock-cell');
    stockCell.innerHTML = '<span class="badge bg-secondary-subtle text-secondary border text-xs">--</span>';

    const qtyInput = newRow.querySelector('.qty-input');
    qtyInput.name = `items[${newIdx}][quantity]`;
    qtyInput.value = '1';

    const priceInput = newRow.querySelector('.price-input');
    priceInput.name = `items[${newIdx}][unit_price]`;
    priceInput.value = '0.00';

    const subtotalCell = newRow.querySelector('.subtotal-cell');
    subtotalCell.innerText = 'Rs. 0.00';

    const removeBtn = newRow.querySelector('.remove-row-btn');
    removeBtn.disabled = false;

    tbody.appendChild(newRow);
    refreshRowIndices();
    calculateGrandSummary();
}

function removeItemRow(btn) {
    const tbody = document.getElementById('itemsTableBody');
    if (!tbody) return;

    const rows = tbody.querySelectorAll('.item-row');
    if (rows.length <= 1) return; // Keep at least one row

    const row = btn.closest('tr');
    if (row) {
        row.remove();
        refreshRowIndices();
        calculateGrandSummary();
    }
}

function refreshRowIndices() {
    const tbody = document.getElementById('itemsTableBody');
    if (!tbody) return;

    const rows = tbody.querySelectorAll('.item-row');
    rows.forEach((r, idx) => {
        const numCell = r.querySelector('.row-num');
        if (numCell) numCell.innerText = (idx + 1);

        const removeBtn = r.querySelector('.remove-row-btn');
        if (removeBtn) {
            removeBtn.disabled = (rows.length === 1);
        }
    });
}

function calculateGrandSummary() {
    const tbody = document.getElementById('itemsTableBody');
    if (!tbody) return;

    const rows = tbody.querySelectorAll('.item-row');
    let totalQty = 0;
    let grandTotal = 0;

    rows.forEach(r => {
        const qtyInput = r.querySelector('.qty-input');
        const priceInput = r.querySelector('.price-input');

        const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
        const price = parseFloat(priceInput ? priceInput.value : 0) || 0;

        totalQty += qty;
        grandTotal += (qty * price);
    });

    const itemsCountElem = document.getElementById('summary_items_count');
    if (itemsCountElem) itemsCountElem.innerText = rows.length;

    const totalQtyElem = document.getElementById('summary_total_qty');
    if (totalQtyElem) totalQtyElem.innerText = totalQty.toFixed(1) + ' pcs';

    const grandTotalElem = document.getElementById('summary_grand_total');
    if (grandTotalElem) grandTotalElem.innerText = 'Rs. ' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function triggerDeleteModal(id, productName, quantity, staffName, month) {
    document.getElementById('modal_product_name').innerText = productName;
    document.getElementById('modal_staff_name').innerText = staffName;
    document.getElementById('modal_quantity').innerText = quantity + ' pcs';
    document.getElementById('modal_qty_text').innerText = quantity;
    document.getElementById('modal_confirm_delete_btn').href = '<?php echo BASE_URL; ?>modules/staff/food_consumption.php?action=delete&id=' + id + '&month=' + month;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

function updateEditUnitPrice() {
    const select = document.getElementById('edit_product_id');
    if (!select) return;
    const selectedOption = select.options[select.selectedIndex];
    const price = parseFloat(selectedOption.getAttribute('data-retail-price')) || 0;
    const priceInput = document.getElementById('edit_unit_price');
    if (priceInput) priceInput.value = price.toFixed(2);
    calculateEditTotal();
}

function calculateEditTotal() {
    const qtyInput = document.getElementById('edit_quantity');
    const priceInput = document.getElementById('edit_unit_price');
    const display = document.getElementById('edit_total_display');
    if (!qtyInput || !priceInput || !display) return;
    const qty = parseFloat(qtyInput.value) || 0;
    const price = parseFloat(priceInput.value) || 0;
    const total = qty * price;
    display.innerText = 'Rs. ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
