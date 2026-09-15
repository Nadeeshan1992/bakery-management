<?php
// modules/orders/edit.php - Edit Existing Order / Bill
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireRole(['admin', 'owner', 'pos_operator']);

$db = getDB();
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    header('Location: ' . BASE_URL . 'modules/orders/index.php');
    exit;
}

// Fetch order items
$stmtItems = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmtItems->execute([$orderId]);
$orderItems = $stmtItems->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = intval($_POST['customer_id'] ?? 0);
    $orderType = $_POST['order_type'] ?? $order['order_type'];
    $orderStatus = $_POST['order_status'] ?? $order['order_status'];
    $paymentStatus = $_POST['payment_status'] ?? $order['payment_status'];
    $paymentMethod = $_POST['payment_method'] ?? $order['payment_method'];
    $chequeRef = trim($_POST['cheque_ref'] ?? '');
    $discount = floatval($_POST['discount'] ?? 0);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    $customNotes = trim($_POST['custom_notes'] ?? '');
    $deliveryDate = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : NULL;

    $productIds = $_POST['product_ids'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $unitPrices = $_POST['unit_prices'] ?? [];

    if (empty($productIds)) {
        setFlash('error', 'Order must contain at least one product item.');
    } else {
        try {
            $db->beginTransaction();

            // 1. Calculate new subtotal & prepare new items
            $newSubtotal = 0;
            $newItems = [];
            
            foreach ($productIds as $idx => $pId) {
                $pId = intval($pId);
                $qty = floatval($quantities[$idx] ?? 1);
                $price = floatval($unitPrices[$idx] ?? 0);
                if ($pId > 0 && $qty > 0) {
                    $stmtP = $db->prepare("SELECT name FROM products WHERE id = ?");
                    $stmtP->execute([$pId]);
                    $pName = $stmtP->fetchColumn() ?: 'Item #' . $pId;

                    $itemSubtotal = $price * $qty;
                    $newSubtotal += $itemSubtotal;
                    $newItems[] = [
                        'product_id' => $pId,
                        'product_name' => $pName,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'subtotal' => $itemSubtotal
                    ];
                }
            }

            $newTotalAmount = max(0, $newSubtotal - $discount);
            $newChangeAmount = max(0, $paidAmount - $newTotalAmount);

            // 2. Reverse stock deductions from old items
            foreach ($orderItems as $oldItem) {
                if ($oldItem['product_id'] > 0 && $oldItem['quantity'] > 0) {
                    $stmtRestock = $db->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
                    $stmtRestock->execute([$oldItem['quantity'], $oldItem['product_id']]);
                }
            }

            // 3. Deduct stock for new items
            $stmtDeduct = $db->prepare("UPDATE products SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?");
            $stmtStockLog = $db->prepare("
                INSERT INTO product_stock_logs (product_id, log_type, quantity, reference_order_id, notes, created_by) 
                VALUES (?, 'adjustment', ?, ?, ?, ?)
            ");

            foreach ($newItems as $newItem) {
                $stmtDeduct->execute([$newItem['quantity'], $newItem['product_id']]);
                $stmtStockLog->execute([$newItem['product_id'], $newItem['quantity'], $orderId, 'Updated Order #' . $order['order_number'], getCurrentUserId()]);
            }

            // 4. Delete old order_items and insert new order_items
            $db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);

            $stmtInsertItem = $db->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($newItems as $newItem) {
                $stmtInsertItem->execute([
                    $orderId,
                    $newItem['product_id'],
                    $newItem['product_name'],
                    $newItem['quantity'],
                    $newItem['unit_price'],
                    $newItem['subtotal']
                ]);
            }

            // 5. Update orders table record
            $stmtUpdateOrder = $db->prepare("
                UPDATE orders SET 
                    customer_id = ?, 
                    order_type = ?, 
                    subtotal = ?, 
                    discount = ?, 
                    total_amount = ?, 
                    paid_amount = ?, 
                    change_amount = ?, 
                    payment_method = ?, 
                    cheque_ref = ?, 
                    payment_status = ?, 
                    order_status = ?, 
                    delivery_date = ?, 
                    custom_notes = ? 
                WHERE id = ?
            ");
            $stmtUpdateOrder->execute([
                $customerId,
                $orderType,
                $newSubtotal,
                $discount,
                $newTotalAmount,
                $paidAmount,
                $newChangeAmount,
                $paymentMethod,
                (!empty($chequeRef) ? $chequeRef : NULL),
                $paymentStatus,
                $orderStatus,
                $deliveryDate,
                $customNotes,
                $orderId
            ]);

            $db->commit();

            setFlash('success', 'Order / Bill #' . htmlspecialchars($order['order_number']) . ' updated successfully!');
            header('Location: ' . BASE_URL . 'modules/orders/view.php?id=' . $orderId);
            exit;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setFlash('error', 'Error updating order: ' . $e->getMessage());
        }
    }
}

$customers = $db->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll();
$products = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY c.name ASC, p.name ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card card-bakery p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Bill / Order #<?php echo htmlspecialchars($order['order_number']); ?></h4>
                    <span class="badge bg-light text-dark border mt-1">Order Type: <?php echo strtoupper($order['order_type']); ?></span>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo BASE_URL; ?>modules/orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>
            </div>

            <form method="POST" action="" id="editOrderForm">
                <div class="row g-3">
                    <!-- Customer & Order Type -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">Customer *</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="0">Walk-in Customer</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $order['customer_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(($c['title'] ?? '') . ' ' . $c['name']); ?> (<?php echo htmlspecialchars($c['phone']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Order Type</label>
                        <select name="order_type" class="form-select">
                            <option value="pos" <?php echo ($order['order_type'] == 'pos') ? 'selected' : ''; ?>>POS Counter</option>
                            <option value="preorder" <?php echo ($order['order_type'] == 'preorder') ? 'selected' : ''; ?>>Pre-Order</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Delivery / Order Date</label>
                        <input type="datetime-local" name="delivery_date" class="form-control" value="<?php echo !empty($order['delivery_date']) ? date('Y-m-d\TH:i', strtotime($order['delivery_date'])) : ''; ?>">
                    </div>

                    <!-- Items Table -->
                    <div class="col-12 mt-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-basket-shopping text-warning me-2"></i> Bill Line Items</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addOrderItemRow()">
                                <i class="fa-solid fa-plus me-1"></i> Add Product Item
                            </button>
                        </div>

                        <div class="row g-2 mb-2 px-2 text-muted text-xs fw-bold text-uppercase border-bottom pb-2 d-none d-md-flex">
                            <div class="col-md-5">Product Name</div>
                            <div class="col-md-2 text-center">Quantity</div>
                            <div class="col-md-2 text-end">Unit Price (Rs.)</div>
                            <div class="col-md-2 text-end">Subtotal</div>
                            <div class="col-md-1 text-center">Action</div>
                        </div>

                        <div id="orderItemsWrapper">
                            <?php foreach ($orderItems as $item): ?>
                                <div class="row g-2 mb-2 order-item-row align-items-center bg-light p-2 rounded border">
                                    <div class="col-md-5">
                                        <select name="product_ids[]" class="form-select form-select-sm product-select" onchange="onProductSelectChange(this)" required>
                                            <option value="">-- Select Product --</option>
                                            <?php foreach ($products as $p): ?>
                                                <?php $pPrice = ($p['price_retail'] > 0) ? $p['price_retail'] : $p['price']; ?>
                                                <option value="<?php echo $p['id']; ?>" data-price="<?php echo $pPrice; ?>" <?php echo ($p['id'] == $item['product_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($p['name']); ?> (Rs. <?php echo number_format($pPrice, 2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" step="any" min="0.01" name="quantities[]" class="form-control form-control-sm qty-input text-center fw-bold" value="<?php echo $item['quantity']; ?>" oninput="calculateTotals()" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" step="0.01" min="0" name="unit_prices[]" class="form-control form-control-sm price-input text-end fw-semibold" value="<?php echo $item['unit_price']; ?>" oninput="calculateTotals()" required>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <span class="row-subtotal-display fw-bold text-dark" style="font-size: 0.95rem;"><?php echo formatMoney($item['subtotal']); ?></span>
                                    </div>
                                    <div class="col-md-1 text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeRow(this)" title="Remove Item"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addOrderItemRow()">
                            <i class="fa-solid fa-plus me-1"></i> Add Another Item
                        </button>
                    </div>

                    <!-- Payment & Status Section -->
                    <div class="col-12 mt-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-wallet text-warning me-2"></i> Payment & Order Workflow Status</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label font-weight-bold">Order Status</label>
                                <select name="order_status" class="form-select">
                                    <option value="pending" <?php echo ($order['order_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_production" <?php echo ($order['order_status'] == 'in_production') ? 'selected' : ''; ?>>In Production</option>
                                    <option value="ready" <?php echo ($order['order_status'] == 'ready') ? 'selected' : ''; ?>>Ready</option>
                                    <option value="completed" <?php echo ($order['order_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($order['order_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label font-weight-bold">Payment Status</label>
                                <select name="payment_status" class="form-select">
                                    <option value="unpaid" <?php echo ($order['payment_status'] == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                                    <option value="partial" <?php echo ($order['payment_status'] == 'partial') ? 'selected' : ''; ?>>Partial Paid</option>
                                    <option value="paid" <?php echo ($order['payment_status'] == 'paid') ? 'selected' : ''; ?>>Fully Paid</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label font-weight-bold">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="cash" <?php echo ($order['payment_method'] == 'cash') ? 'selected' : ''; ?>>Cash</option>
                                    <option value="cheque" <?php echo ($order['payment_method'] == 'cheque') ? 'selected' : ''; ?>>Cheque</option>
                                    <option value="online" <?php echo ($order['payment_method'] == 'online') ? 'selected' : ''; ?>>Online Transfer</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label font-weight-bold">Cheque / Ref No.</label>
                                <input type="text" name="cheque_ref" class="form-control" value="<?php echo htmlspecialchars($order['cheque_ref'] ?? ''); ?>" placeholder="Reference No.">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Discount (Rs.)</label>
                                <input type="number" step="0.01" min="0" name="discount" id="discountInput" class="form-control text-end fw-semibold text-danger" value="<?php echo $order['discount']; ?>" oninput="calculateTotals()">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Amount Paid (Rs.)</label>
                                <input type="number" step="0.01" min="0" name="paid_amount" id="paidInput" class="form-control text-end fw-bold text-success" value="<?php echo $order['paid_amount']; ?>" oninput="calculateTotals()">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Custom Notes / Specs</label>
                                <input type="text" name="custom_notes" class="form-control" value="<?php echo htmlspecialchars($order['custom_notes'] ?? ''); ?>" placeholder="Order notes...">
                            </div>
                        </div>
                    </div>

                    <!-- Totals Summary Card -->
                    <div class="col-12 mt-3">
                        <div class="card bg-light p-3 border">
                            <div class="row text-end align-items-center">
                                <div class="col-md-6 text-start text-muted">
                                    <small><i class="fa-solid fa-calculator me-1"></i> Calculation Summary</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Subtotal:</span>
                                        <strong id="displaySubtotal" class="text-dark">Rs. 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 text-danger">
                                        <span>Discount:</span>
                                        <strong id="displayDiscount">- Rs. 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 border-top pt-1 fs-5 fw-bold">
                                        <span>Grand Total:</span>
                                        <strong id="displayTotal" class="text-success">Rs. 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between text-primary">
                                        <span>Balance Due:</span>
                                        <strong id="displayBalance">Rs. 0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="submit" class="btn btn-warning text-dark fw-bold px-4 py-2">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes to Bill
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function onProductSelectChange(select) {
    const row = select.closest('.order-item-row');
    const priceInput = row.querySelector('.price-input');
    const opt = select.options[select.selectedIndex];
    const price = parseFloat(opt ? opt.getAttribute('data-price') || 0 : 0);
    if (priceInput) {
        priceInput.value = price.toFixed(2);
    }
    calculateTotals();
}

function addOrderItemRow() {
    const wrapper = document.getElementById('orderItemsWrapper');
    const rowHtml = `
        <div class="row g-2 mb-2 order-item-row align-items-center bg-light p-2 rounded border">
            <div class="col-md-5">
                <select name="product_ids[]" class="form-select form-select-sm product-select" onchange="onProductSelectChange(this)" required>
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $p): ?>
                        <?php $pPrice = ($p['price_retail'] > 0) ? $p['price_retail'] : $p['price']; ?>
                        <option value="<?php echo $p['id']; ?>" data-price="<?php echo $pPrice; ?>">
                            <?php echo htmlspecialchars($p['name']); ?> (Rs. <?php echo number_format($pPrice, 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" step="any" min="0.01" name="quantities[]" class="form-control form-control-sm qty-input text-center fw-bold" value="1" oninput="calculateTotals()" required>
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" min="0" name="unit_prices[]" class="form-control form-control-sm price-input text-end fw-semibold" value="0.00" oninput="calculateTotals()" required>
            </div>
            <div class="col-md-2 text-end">
                <span class="row-subtotal-display fw-bold text-dark" style="font-size: 0.95rem;">Rs. 0.00</span>
            </div>
            <div class="col-md-1 text-center">
                <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeRow(this)" title="Remove Item"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>`;
    wrapper.insertAdjacentHTML('beforeend', rowHtml);
    calculateTotals();
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.order-item-row');
    if (rows.length > 1) {
        btn.closest('.order-item-row').remove();
        calculateTotals();
    }
}

function calculateTotals() {
    const rows = document.querySelectorAll('.order-item-row');
    let subtotal = 0;

    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input')?.value || 0);
        const price = parseFloat(row.querySelector('.price-input')?.value || 0);
        const itemSub = qty * price;
        subtotal += itemSub;

        const subDisplay = row.querySelector('.row-subtotal-display');
        if (subDisplay) {
            subDisplay.innerText = 'Rs. ' + itemSub.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });

    const discount = parseFloat(document.getElementById('discountInput')?.value || 0);
    const paid = parseFloat(document.getElementById('paidInput')?.value || 0);

    const grandTotal = Math.max(0, subtotal - discount);
    const balance = Math.max(0, grandTotal - paid);

    document.getElementById('displaySubtotal').innerText = 'Rs. ' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('displayDiscount').innerText = '- Rs. ' + discount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('displayTotal').innerText = 'Rs. ' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('displayBalance').innerText = 'Rs. ' + balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.addEventListener('DOMContentLoaded', function() {
    calculateTotals();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
