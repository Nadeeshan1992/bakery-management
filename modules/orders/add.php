<?php
// modules/orders/add.php - Book Custom Cake & Pre-Order
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sms_helper.php';

requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = intval($_POST['customer_id'] ?? 0);
    $orderType = $_POST['order_type'] ?? 'preorder';
    $deliveryDate = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : NULL;
    $customNotes = trim($_POST['custom_notes'] ?? '');
    $selectedProductIds = $_POST['product_ids'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $discount = floatval($_POST['discount'] ?? 0);
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $chequeRef = trim($_POST['cheque_ref'] ?? '');

    $returnProductIds = $_POST['return_product_ids'] ?? [];
    $returnQuantities = $_POST['return_quantities'] ?? [];
    $returnReasons = $_POST['return_reasons'] ?? [];

    if ($customerId <= 0 || empty($selectedProductIds)) {
        setFlash('error', 'Please select a customer and at least one product.');
    } else {
        try {
            $db->beginTransaction();

            $subtotal = 0;
            $itemsToInsert = [];

            foreach ($selectedProductIds as $idx => $prodId) {
                $prodId = intval($prodId);
                $qty = intval($quantities[$idx] ?? 1);
                if ($prodId > 0 && $qty > 0) {
                    $stmtP = $db->prepare("SELECT name, price_retail, price, current_stock FROM products WHERE id = ?");
                    $stmtP->execute([$prodId]);
                    $pData = $stmtP->fetch();

                    if ($pData) {
                        if (floatval($pData['current_stock']) < $qty) {
                            $avail = intval($pData['current_stock']);
                            throw new Exception('Cannot book order: "' . htmlspecialchars($pData['name']) . '" is OUT OF STOCK! (Available: ' . $avail . ' pcs, Requested: ' . $qty . ' pcs). Please enter daily item stock first.');
                        }
                        $unitPrice = floatval($pData['price_retail'] > 0 ? $pData['price_retail'] : $pData['price']);
                        $itemSubtotal = $unitPrice * $qty;
                        $subtotal += $itemSubtotal;
                        $itemsToInsert[] = [
                            'product_id' => $prodId,
                            'name' => $pData['name'],
                            'price' => $unitPrice,
                            'qty' => $qty,
                            'subtotal' => $itemSubtotal
                        ];
                    }
                }
            }

            // Calculate Return Goods Financial Credit
            $totalReturnCredit = 0;
            foreach ($returnProductIds as $rIdx => $rProdId) {
                $rProdId = intval($rProdId);
                $rQty = floatval($returnQuantities[$rIdx] ?? 0);
                if ($rProdId > 0 && $rQty > 0) {
                    $stmtRP = $db->prepare("SELECT price_retail, price FROM products WHERE id = ?");
                    $stmtRP->execute([$rProdId]);
                    $rPData = $stmtRP->fetch();
                    if ($rPData) {
                        $rUnitPrice = floatval(($rPData['price_retail'] > 0) ? $rPData['price_retail'] : $rPData['price']);
                        $totalReturnCredit += $rUnitPrice * $rQty;
                    }
                }
            }

            $totalAmount = max(0, $subtotal - $discount - $totalReturnCredit);
            $paymentStatus = ($paidAmount >= $totalAmount) ? 'paid' : (($paidAmount > 0) ? 'partial' : 'unpaid');
            $orderStatus = ($paymentStatus === 'paid') ? 'completed' : 'pending';
            $changeAmount = max(0, $paidAmount - $totalAmount);

            // Server-Side Credit Limit Validation
            $stmtCustCheck = $db->prepare("SELECT credit_limit, opening_balance FROM customers WHERE id = ?");
            $stmtCustCheck->execute([$customerId]);
            $cCheckData = $stmtCustCheck->fetch();
            if ($cCheckData) {
                $creditLimitCheck = floatval($cCheckData['credit_limit'] ?? 0);
                if ($creditLimitCheck > 0) {
                    $stmtUnpaid = $db->prepare("SELECT COALESCE(SUM(total_amount - paid_amount), 0) as unpaid_sum FROM orders WHERE customer_id = ? AND order_status != 'cancelled' AND payment_status != 'paid'");
                    $stmtUnpaid->execute([$customerId]);
                    $unpaidSum = floatval($stmtUnpaid->fetch()['unpaid_sum'] ?? 0);
                    $currentOutstanding = floatval($cCheckData['opening_balance'] ?? 0) + $unpaidSum;
                    $availableCredit = max(0, $creditLimitCheck - $currentOutstanding);
                    $balanceDueCheck = max(0, $totalAmount - $paidAmount);
                    if ($balanceDueCheck > $availableCredit) {
                        throw new Exception('Cannot book order: Balance due (Rs. ' . number_format($balanceDueCheck, 2) . ') exceeds customer\'s available credit limit (Rs. ' . number_format($availableCredit, 2) . '). Total Credit Limit: Rs. ' . number_format($creditLimitCheck, 2) . ', Outstanding: Rs. ' . number_format($currentOutstanding, 2) . '.');
                    }
                }
            }

            $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

            $stmtOrder = $db->prepare("
                INSERT INTO orders (order_number, customer_id, order_type, subtotal, discount, tax, total_amount, paid_amount, change_amount, payment_method, cheque_ref, payment_status, order_status, delivery_date, custom_notes, created_by) 
                VALUES (?, ?, ?, ?, ?, 0.00, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtOrder->execute([
                $orderNumber,
                $customerId,
                $orderType,
                $subtotal,
                ($discount + $totalReturnCredit),
                $totalAmount,
                $paidAmount,
                $changeAmount,
                $paymentMethod,
                (!empty($chequeRef) ? $chequeRef : NULL),
                $paymentStatus,
                $orderStatus,
                $deliveryDate,
                $customNotes,
                getCurrentUserId()
            ]);

            $orderId = $db->lastInsertId();

            if ($paidAmount > 0) {
                $stmtPayInit = $db->prepare("
                    INSERT INTO order_payments (order_id, payment_amount, payment_method, cheque_ref, payment_type, notes, created_by) 
                    VALUES (?, ?, ?, ?, 'advance', ?, ?)
                ");
                $stmtPayInit->execute([
                    $orderId,
                    $paidAmount,
                    $paymentMethod,
                    (!empty($chequeRef) ? $chequeRef : NULL),
                    'Advance deposit on pre-order booking',
                    getCurrentUserId()
                ]);
            }

            // Insert Order Items & Deduct Stock
            $stmtItem = $db->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtDeductStock = $db->prepare("UPDATE products SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?");
            $stmtStockLog = $db->prepare("
                INSERT INTO product_stock_logs (product_id, log_type, quantity, reference_order_id, notes, created_by) 
                VALUES (?, 'sale_deduction', ?, ?, ?, ?)
            ");

            foreach ($itemsToInsert as $item) {
                $stmtItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    $item['qty'],
                    $item['price'],
                    $item['subtotal']
                ]);

                // Deduct finished product stock & record audit log
                if ($item['product_id'] > 0 && $item['qty'] > 0) {
                    $stmtDeductStock->execute([$item['qty'], $item['product_id']]);
                    $stmtStockLog->execute([$item['product_id'], $item['qty'], $orderId, 'Pre-Order Booking #' . $orderNumber, getCurrentUserId()]);
                }
            }

            // Process Customer Returned Goods (Expired vs Over Order)
            $stmtReturn = $db->prepare("
                INSERT INTO order_returns (order_id, customer_id, product_id, quantity, unit_price, total_value, reason, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtRestock = $db->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
            $stmtStockLogReturn = $db->prepare("
                INSERT INTO product_stock_logs (product_id, log_type, quantity, reference_order_id, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($returnProductIds as $rIdx => $rProdId) {
                $rProdId = intval($rProdId);
                $rQty = floatval($returnQuantities[$rIdx] ?? 0);
                $rReason = $returnReasons[$rIdx] ?? 'over_order';

                if ($rProdId > 0 && $rQty > 0) {
                    $stmtRP = $db->prepare("SELECT name, price_retail, price FROM products WHERE id = ?");
                    $stmtRP->execute([$rProdId]);
                    $rPData = $stmtRP->fetch();
                    $rUnitPrice = floatval(($rPData['price_retail'] > 0) ? $rPData['price_retail'] : $rPData['price']);
                    $rTotalVal = $rUnitPrice * $rQty;

                    $stmtReturn->execute([
                        $orderId,
                        $customerId,
                        $rProdId,
                        $rQty,
                        $rUnitPrice,
                        $rTotalVal,
                        $rReason,
                        getCurrentUserId()
                    ]);

                    if ($rReason === 'over_order') {
                        // Over Order return -> Restock into sellable stock
                        $stmtRestock->execute([$rQty, $rProdId]);
                        $stmtStockLogReturn->execute([$rProdId, 'adjustment', $rQty, $orderId, 'Customer Over-Order Return restocked on Order #' . $orderNumber, getCurrentUserId()]);
                    } else {
                        // Expired return -> Log as waste / spoilage loss (NOT added to stock)
                        $stmtStockLogReturn->execute([$rProdId, 'waste', $rQty, $orderId, 'Customer Expired Goods Return written off on Order #' . $orderNumber, getCurrentUserId()]);
                    }
                }
            }

            $db->commit();

            // Send automated customer SMS confirmation
            try {
                sendOrderConfirmationSMS($orderId, $db);
            } catch (Exception $e) {
                error_log("Failed to send preorder SMS: " . $e->getMessage());
            }

            setFlash('success', 'Pre-Order #' . $orderNumber . ' booked successfully!');
            header('Location: ' . BASE_URL . 'modules/orders/view.php?id=' . $orderId);
            exit;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setFlash('error', 'Booking failed: ' . $e->getMessage());
        }
    }
}

$customers = $db->query("
    SELECT c.*,
           COALESCE(c.credit_limit, 0) as credit_limit,
           (COALESCE(c.opening_balance, 0) + COALESCE(
               (SELECT SUM(total_amount - paid_amount) 
                FROM orders 
                WHERE customer_id = c.id 
                  AND order_status != 'cancelled' 
                  AND payment_status != 'paid'), 0
           )) AS total_outstanding
    FROM customers c 
    ORDER BY c.name ASC
")->fetchAll();
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$products = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY c.name ASC, p.name ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card card-bakery p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0"><i class="fa-solid fa-calendar-check text-warning me-2"></i> Book Pre-Order</h4>
                <a href="<?php echo BASE_URL; ?>modules/orders/index.php" class="btn btn-outline-secondary btn-sm">Back to Orders</a>
            </div>

            <form method="POST" action="" id="orderBookingForm">
                <div class="row g-3">
                    <!-- Customer & Order Type -->
                    <div class="col-md-7">
                        <label class="form-label font-weight-bold">Select Customer *</label>
                        <select name="customer_id" id="customerSelect" class="form-select" onchange="handleCustomerSelectChange()" required>
                            <option value="">-- Choose Customer --</option>
                            <?php foreach ($customers as $c): ?>
                                <?php 
                                    $discBisc = floatval($c['discount_biscuits'] ?? $c['special_discount'] ?? 0);
                                    $discOther = floatval($c['discount_other'] ?? $c['special_discount'] ?? 0);
                                    $cCreditLimit = floatval($c['credit_limit'] ?? 0);
                                    $cOutstanding = floatval($c['total_outstanding'] ?? 0);
                                    $cAvailable = max(0, $cCreditLimit - $cOutstanding);
                                ?>
                                <option value="<?php echo $c['id']; ?>" 
                                        data-discount-biscuits="<?php echo $discBisc; ?>" 
                                        data-discount-other="<?php echo $discOther; ?>"
                                        data-credit-limit="<?php echo $cCreditLimit; ?>"
                                        data-outstanding="<?php echo $cOutstanding; ?>"
                                        data-available-credit="<?php echo $cAvailable; ?>">
                                    <?php echo htmlspecialchars(($c['title'] ?? '') . ' ' . $c['name']); ?> (<?php echo htmlspecialchars($c['phone']); ?>)
                                    <?php if ($cCreditLimit > 0): ?> [Credit Limit: Rs. <?php echo number_format($cCreditLimit, 2); ?> | Avail: Rs. <?php echo number_format($cAvailable, 2); ?>]<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted"><a href="<?php echo BASE_URL; ?>modules/customers/add.php" target="_blank">+ Register New Customer</a></small>

                        <!-- Customer Credit Status Banner -->
                        <div id="customerCreditBanner" class="card mt-2 p-2 bg-light border" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center text-xs">
                                <div><i class="fa-solid fa-credit-card text-primary me-1"></i> <strong>Credit Limit:</strong> <span id="creditLimitVal" class="fw-bold">Rs. 0.00</span></div>
                                <div><i class="fa-solid fa-file-invoice-dollar text-warning me-1"></i> <strong>Outstanding:</strong> <span id="outstandingVal" class="fw-bold text-danger">Rs. 0.00</span></div>
                                <div><i class="fa-solid fa-wallet text-success me-1"></i> <strong>Available Credit:</strong> <span id="availableCreditVal" class="fw-bold text-success">Rs. 0.00</span></div>
                            </div>
                        </div>

                        <div id="creditExceededAlert" class="alert alert-danger mt-2 py-2 px-3 align-items-center mb-0" style="display: none;">
                            <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                            <div>
                                <strong>Credit Limit Exceeded!</strong> Balance due (<span id="alertBalanceDue">Rs. 0.00</span>) exceeds available credit (<span id="alertAvailCredit">Rs. 0.00</span>). Order cannot be created.
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label font-weight-bold">Order Type *</label>
                        <select name="order_type" id="orderTypeSelect" class="form-select">
                            <option value="preorder" selected>Pre-Order</option>
                        </select>
                    </div>

                    <!-- Products Selector -->
                    <div class="col-12 mt-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-basket-shopping text-warning me-2"></i> Order Items</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addOrderItemRow()">
                                <i class="fa-solid fa-plus me-1"></i> Add Item
                            </button>
                        </div>

                        <!-- Column Headers -->
                        <div class="row g-2 mb-2 px-2 text-muted text-xs fw-bold text-uppercase border-bottom pb-2 d-none d-md-flex">
                            <div class="col-md-2">Category Filter</div>
                            <div class="col-md-4">Product Name & Available Stock</div>
                            <div class="col-md-1 text-center">Qty</div>
                            <div class="col-md-2 text-end">Discount (Rs.)</div>
                            <div class="col-md-2 text-end">Subtotal</div>
                            <div class="col-md-1 text-center">Action</div>
                        </div>

                        <div id="orderItemsWrapper">
                            <div class="row g-2 mb-2 order-item-row align-items-center bg-light p-2 rounded border">
                                <!-- Category Filter -->
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm category-select" onchange="filterRowProducts(this)">
                                        <option value="all">-- All Categories --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Product Select -->
                                <div class="col-md-4">
                                    <select name="product_ids[]" class="form-select form-select-sm product-select" onchange="calculateOrderTotals()" required>
                                        <option value="" data-price="0" data-stock="0" data-unit="pcs">-- Select Product --</option>
                                        <?php foreach ($products as $p): ?>
                                            <?php 
                                                $effectivePrice = ($p['price_retail'] > 0) ? $p['price_retail'] : $p['price']; 
                                                $stock = floatval($p['current_stock'] ?? 0);
                                                $unit = htmlspecialchars($p['unit'] ?? 'pcs');
                                            ?>
                                            <option value="<?php echo $p['id']; ?>" 
                                                    data-category-id="<?php echo $p['category_id']; ?>" 
                                                    data-category-name="<?php echo htmlspecialchars($p['category_name'] ?? ''); ?>" 
                                                    data-price="<?php echo $effectivePrice; ?>"
                                                    data-stock="<?php echo $stock; ?>"
                                                    data-unit="<?php echo $unit; ?>">
                                                <?php echo htmlspecialchars($p['name']); ?> - Rs. <?php echo number_format($effectivePrice, 2); ?> (Stock: <?php echo $stock . ' ' . $unit; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="product-stock-display mt-1 text-xs" style="display: none;">
                                        <span class="stock-badge badge py-1 px-2"></span>
                                    </div>
                                </div>

                                <div class="col-md-1">
                                    <input type="number" name="quantities[]" class="form-control form-control-sm qty-input text-center fw-bold px-1" value="1" min="1" placeholder="Qty" oninput="calculateOrderTotals()">
                                </div>

                                <div class="col-md-2 text-end">
                                    <span class="row-discount-display text-danger fw-bold text-nowrap" style="font-size: 0.85rem;">- Rs. 0.00</span>
                                </div>

                                <div class="col-md-2 text-end">
                                    <span class="row-subtotal-display fw-bold text-dark text-nowrap" style="font-size: 0.95rem;">Rs. 0.00</span>
                                </div>

                                <div class="col-md-1 text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn p-1" onclick="removeRow(this)" title="Delete Row"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addOrderItemRow()">
                            <i class="fa-solid fa-plus me-1"></i> Add Another Item
                        </button>
                    </div>

                    <!-- Customer Returned Goods Section -->
                    <div class="col-12 mt-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-rotate-left text-danger me-2"></i> Customer Returned Goods (Optional)
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-danger fw-bold" onclick="addReturnItemRow()">
                                <i class="fa-solid fa-plus me-1"></i> Log Returned Item
                            </button>
                        </div>

                        <!-- Return Headers -->
                        <div class="row g-2 mb-2 px-2 text-muted text-xs fw-bold text-uppercase border-bottom pb-2 d-none d-md-flex">
                            <div class="col-md-5">Returned Product</div>
                            <div class="col-md-2 text-center">Returned Qty</div>
                            <div class="col-md-4">Return Reason</div>
                            <div class="col-md-1 text-center">Action</div>
                        </div>

                        <div id="returnItemsWrapper">
                            <!-- Dynamic return rows will be appended here -->
                        </div>
                    </div>

                    <!-- Live Calculation Summary Card -->
                    <div class="col-12 mt-3">
                        <div class="card bg-light p-3 border">
                            <div class="row text-end align-items-center">
                                <div class="col-md-6 text-start text-muted">
                                    <small><i class="fa-solid fa-calculator me-1"></i> Live Calculation Summary</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Items Subtotal:</span>
                                        <strong id="displaySubtotal" class="text-dark">Rs. 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Discount:</span>
                                        <strong id="displayDiscount" class="text-danger">- Rs. 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 text-primary">
                                        <span>Returned Goods Credit:</span>
                                        <strong id="displayReturnCredit">- Rs. 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 border-top pt-1 fs-5 fw-bold">
                                        <span>Total Order Amount:</span>
                                        <strong id="displayTotal" class="text-success">Rs. 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between text-primary">
                                        <span>Balance Due (After Deposit):</span>
                                        <strong id="displayBalance">Rs. 0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financials -->
                    <div class="col-12 mt-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-wallet text-warning me-2"></i> Payment & Advance Deposit</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Discount (Rs.)</label>
                                <input type="number" step="0.01" min="0" name="discount" id="inputDiscount" class="form-control" value="0.00" oninput="onManualDiscountChange()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Advance Deposit Paid (Rs.)</label>
                                <input type="number" step="0.01" min="0" name="paid_amount" id="inputPaid" class="form-control" value="0.00" placeholder="Enter advance paid" oninput="calculateOrderTotals()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold">Payment Method</label>
                                <select name="payment_method" id="orderPaymentMethodSelect" class="form-select" onchange="toggleOrderChequeRefField()">
                                    <option value="cash" selected>Cash</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="online">Online Transfer</option>
                                </select>
                            </div>
                            <div class="col-12 mt-2" id="orderChequeRefContainer" style="display: none;">
                                <label class="form-label font-weight-bold text-primary">
                                    <i class="fa-solid fa-money-check me-1"></i> Cheque / Reference No. <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="cheque_ref" id="orderChequeRefInput" class="form-control fw-bold border-primary" placeholder="Enter cheque number (e.g. CHQ-984712)">
                                <small class="text-muted">Enter cheque number or payment transaction reference details.</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="submit" id="btnSubmitOrder" class="btn btn-warning text-dark fw-bold px-4 py-2">
                            <i class="fa-solid fa-check-circle me-1"></i> Complete Order Booking
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let isManualDiscountOverride = false;

function onManualDiscountChange() {
    isManualDiscountOverride = true;
    calculateOrderTotals();
}

function handleCustomerSelectChange() {
    isManualDiscountOverride = false; // Reset manual override when switching customer
    calculateOrderTotals();
}

function filterRowProducts(catSelect) {
    const row = catSelect.closest('.order-item-row');
    const prodSelect = row.querySelector('.product-select');
    const selectedCatId = catSelect.value;

    const options = prodSelect.querySelectorAll('option');
    options.forEach(opt => {
        if (!opt.value) return; // keep placeholder
        const catId = opt.getAttribute('data-category-id');
        if (selectedCatId === 'all' || catId === selectedCatId) {
            opt.style.display = 'block';
            opt.disabled = false;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
        }
    });

    const currentOpt = prodSelect.options[prodSelect.selectedIndex];
    if (currentOpt && currentOpt.disabled) {
        prodSelect.value = '';
    }
    calculateOrderTotals();
}

function handleOrderTypeChange() {
    const orderType = document.getElementById('orderTypeSelect').value;
    const rows = document.querySelectorAll('.order-item-row');
    
    rows.forEach(row => {
        const catSelect = row.querySelector('.category-select');
        if (catSelect) {
            if (orderType === 'custom_cake') {
                for (let i = 0; i < catSelect.options.length; i++) {
                    if (catSelect.options[i].text.toLowerCase().includes('cake')) {
                        catSelect.selectedIndex = i;
                        filterRowProducts(catSelect);
                        break;
                    }
                }
            } else {
                catSelect.value = 'all';
                filterRowProducts(catSelect);
            }
        }
    });
}

function addReturnItemRow() {
    const wrapper = document.getElementById('returnItemsWrapper');
    const returnRowHtml = `
        <div class="row g-2 mb-2 return-item-row align-items-center bg-white p-2 rounded border border-danger-subtle">
            <div class="col-md-5">
                <select name="return_product_ids[]" class="form-select form-select-sm return-product-select" onchange="calculateOrderTotals()" required>
                    <option value="" data-price="0">-- Choose Returned Product --</option>
                    <?php foreach ($products as $p): ?>
                        <?php $rPrice = ($p['price_retail'] > 0) ? $p['price_retail'] : $p['price']; ?>
                        <option value="<?php echo $p['id']; ?>" data-price="<?php echo $rPrice; ?>">
                            <?php echo htmlspecialchars($p['name']); ?> (Rs. <?php echo number_format($rPrice, 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" step="1" min="1" name="return_quantities[]" class="form-control form-control-sm return-qty-input text-center fw-bold" value="1" placeholder="Qty" oninput="calculateOrderTotals()" required>
            </div>
            <div class="col-md-4">
                <select name="return_reasons[]" class="form-select form-select-sm fw-bold">
                    <option value="expired" class="text-danger">Expired / Spoilage (Write-off as Waste)</option>
                    <option value="over_order" class="text-primary">Over Order (Restock to Sellable Stock)</option>
                </select>
            </div>
            <div class="col-md-1 text-center">
                <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeReturnRow(this)" title="Remove Return Row"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>`;
    wrapper.insertAdjacentHTML('beforeend', returnRowHtml);
    calculateOrderTotals();
}

function removeReturnRow(btn) {
    btn.closest('.return-item-row').remove();
    calculateOrderTotals();
}

function addOrderItemRow() {
    const wrapper = document.getElementById('orderItemsWrapper');
    const firstRow = wrapper.querySelector('.order-item-row');
    const newRow = firstRow.cloneNode(true);

    const catSelect = newRow.querySelector('.category-select');
    const prodSelect = newRow.querySelector('.product-select');
    
    catSelect.value = 'all';
    prodSelect.value = '';
    
    // Enable all options in cloned row
    const options = prodSelect.querySelectorAll('option');
    options.forEach(opt => {
        opt.style.display = 'block';
        opt.disabled = false;
    });

    const stockDisplay = newRow.querySelector('.product-stock-display');
    if (stockDisplay) stockDisplay.style.display = 'none';

    const qtyInput = newRow.querySelector('.qty-input');
    qtyInput.value = '1';
    qtyInput.classList.remove('is-invalid');

    newRow.querySelector('.row-discount-display').innerHTML = '<span class="text-muted" style="font-size:0.8rem;">Rs. 0.00</span>';
    newRow.querySelector('.row-subtotal-display').innerText = 'Rs. 0.00';
    wrapper.appendChild(newRow);
    calculateOrderTotals();
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.order-item-row');
    if (rows.length > 1) {
        btn.closest('.order-item-row').remove();
        calculateOrderTotals();
    }
}

function calculateOrderTotals() {
    const rows = document.querySelectorAll('.order-item-row');
    let grandSubtotal = 0;
    let autoCalculatedDiscount = 0;

    const custSelect = document.getElementById('customerSelect');
    const selectedCustOpt = custSelect ? custSelect.options[custSelect.selectedIndex] : null;
    const discBiscuitsPercent = parseFloat(selectedCustOpt?.getAttribute('data-discount-biscuits') || 0);
    const discOtherPercent = parseFloat(selectedCustOpt?.getAttribute('data-discount-other') || 0);

    rows.forEach(row => {
        const select = row.querySelector('.product-select');
        const qtyInput = row.querySelector('.qty-input');
        const subtotalDisplay = row.querySelector('.row-subtotal-display');
        const discountDisplay = row.querySelector('.row-discount-display');

        const selectedOption = select.options[select.selectedIndex];
        const price = parseFloat(selectedOption?.getAttribute('data-price') || 0);
        const catName = (selectedOption?.getAttribute('data-category-name') || '').toLowerCase();
        const stock = parseFloat(selectedOption?.getAttribute('data-stock') || 0);
        const unit = selectedOption?.getAttribute('data-unit') || 'pcs';
        const qty = parseInt(qtyInput.value || 1);

        // Update Live Available Stock Display
        const stockDisplay = row.querySelector('.product-stock-display');
        const stockBadge = row.querySelector('.stock-badge');
        if (select.value && stockDisplay && stockBadge) {
            stockDisplay.style.display = 'block';
            if (stock <= 0) {
                stockBadge.className = 'stock-badge badge bg-danger-subtle text-danger border border-danger-subtle';
                stockBadge.innerHTML = `<i class="fa-solid fa-circle-xmark me-1"></i> Out of Stock (0 ${unit})`;
                qtyInput.classList.add('is-invalid');
            } else if (qty > stock) {
                stockBadge.className = 'stock-badge badge bg-danger text-white border border-danger shadow-sm';
                stockBadge.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i> Exceeds Stock! (Avail: <strong>${stock} ${unit}</strong>)`;
                qtyInput.classList.add('is-invalid');
            } else if (stock <= 10) {
                stockBadge.className = 'stock-badge badge bg-warning-subtle text-dark border border-warning-subtle';
                stockBadge.innerHTML = `<i class="fa-solid fa-boxes-stacked me-1 text-warning"></i> Available Stock: <strong>${stock} ${unit}</strong>`;
                qtyInput.classList.remove('is-invalid');
            } else {
                stockBadge.className = 'stock-badge badge bg-success-subtle text-success border border-success-subtle';
                stockBadge.innerHTML = `<i class="fa-solid fa-boxes-stacked me-1 text-success"></i> Available Stock: <strong>${stock} ${unit}</strong>`;
                qtyInput.classList.remove('is-invalid');
            }
        } else if (stockDisplay) {
            stockDisplay.style.display = 'none';
            qtyInput.classList.remove('is-invalid');
        }

        const grossRowSubtotal = price * qty;
        grandSubtotal += grossRowSubtotal;

        // Apply Category Discount Rates for this row item
        let appliedPercent = discOtherPercent;
        if (catName.includes('biscuit') || catName.includes('cookie')) {
            appliedPercent = discBiscuitsPercent;
        }

        let rowDiscountAmount = 0;
        if (appliedPercent > 0 && grossRowSubtotal > 0) {
            rowDiscountAmount = (grossRowSubtotal * appliedPercent) / 100;
        }

        autoCalculatedDiscount += rowDiscountAmount;
        const netRowSubtotal = Math.max(0, grossRowSubtotal - rowDiscountAmount);

        // Render Itemized Customer Discount in Row
        if (discountDisplay) {
            if (appliedPercent > 0 && rowDiscountAmount > 0) {
                discountDisplay.innerHTML = `<span class="badge bg-warning text-dark me-1" style="font-size:0.7rem;">${appliedPercent}%</span><small class="text-danger fw-bold">- Rs. ${rowDiscountAmount.toFixed(2)}</small>`;
            } else {
                discountDisplay.innerHTML = `<span class="text-muted" style="font-size:0.8rem;">Rs. 0.00</span>`;
            }
        }

        if (subtotalDisplay) {
            subtotalDisplay.innerText = 'Rs. ' + netRowSubtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });

    // Calculate Return Goods Financial Credit Total
    let totalReturnCredit = 0;
    const returnRows = document.querySelectorAll('.return-item-row');
    returnRows.forEach(row => {
        const rSelect = row.querySelector('.return-product-select');
        const rQtyInput = row.querySelector('.return-qty-input');
        if (rSelect && rQtyInput) {
            const rOpt = rSelect.options[rSelect.selectedIndex];
            const rPrice = parseFloat(rOpt?.getAttribute('data-price') || 0);
            const rQty = parseFloat(rQtyInput.value || 0);
            totalReturnCredit += rPrice * rQty;
        }
    });

    const discountInput = document.getElementById('inputDiscount');
    if (discountInput && !isManualDiscountOverride) {
        discountInput.value = autoCalculatedDiscount.toFixed(2);
    }

    const discount = parseFloat(discountInput?.value || 0);
    const paid = parseFloat(document.getElementById('inputPaid')?.value || 0);

    const totalAmount = Math.max(0, grandSubtotal - discount - totalReturnCredit);
    const balanceDue = Math.max(0, totalAmount - paid);

    document.getElementById('displaySubtotal').innerText = 'Rs. ' + grandSubtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('displayDiscount').innerText = '- Rs. ' + discount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (document.getElementById('displayReturnCredit')) {
        document.getElementById('displayReturnCredit').innerText = '- Rs. ' + totalReturnCredit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    document.getElementById('displayTotal').innerText = 'Rs. ' + totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('displayBalance').innerText = 'Rs. ' + balanceDue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Customer Credit Limit Verification & Enforcement
    const creditBanner = document.getElementById('customerCreditBanner');
    const creditAlert = document.getElementById('creditExceededAlert');
    const submitBtn = document.getElementById('btnSubmitOrder');

    if (selectedCustOpt && selectedCustOpt.value) {
        const creditLimit = parseFloat(selectedCustOpt.getAttribute('data-credit-limit') || 0);
        const outstanding = parseFloat(selectedCustOpt.getAttribute('data-outstanding') || 0);
        const availableCredit = parseFloat(selectedCustOpt.getAttribute('data-available-credit') || 0);

        if (creditLimit > 0) {
            if (creditBanner) creditBanner.style.display = 'block';
            document.getElementById('creditLimitVal').innerText = 'Rs. ' + creditLimit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('outstandingVal').innerText = 'Rs. ' + outstanding.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('availableCreditVal').innerText = 'Rs. ' + availableCredit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            if (balanceDue > availableCredit) {
                if (creditAlert) {
                    creditAlert.style.display = 'flex';
                    document.getElementById('alertBalanceDue').innerText = 'Rs. ' + balanceDue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    document.getElementById('alertAvailCredit').innerText = 'Rs. ' + availableCredit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.remove('btn-warning');
                    submitBtn.classList.add('btn-secondary');
                }
            } else {
                if (creditAlert) creditAlert.style.display = 'none';
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-secondary');
                    submitBtn.classList.add('btn-warning');
                }
            }
        } else {
            if (creditBanner) creditBanner.style.display = 'none';
            if (creditAlert) creditAlert.style.display = 'none';
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-secondary');
                submitBtn.classList.add('btn-warning');
            }
        }
    } else {
        if (creditBanner) creditBanner.style.display = 'none';
        if (creditAlert) creditAlert.style.display = 'none';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-secondary');
            submitBtn.classList.add('btn-warning');
        }
    }
}

function toggleOrderChequeRefField() {
    const select = document.getElementById('orderPaymentMethodSelect');
    const container = document.getElementById('orderChequeRefContainer');
    const input = document.getElementById('orderChequeRefInput');
    if (!select || !container || !input) return;

    if (select.value === 'cheque') {
        container.style.display = 'block';
        input.required = true;
        input.placeholder = "Enter cheque number (e.g. CHQ-984712)";
    } else if (select.value === 'online') {
        container.style.display = 'block';
        input.required = false;
        input.placeholder = "Enter online transfer reference no. (Optional)";
    } else {
        container.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    handleOrderTypeChange();
    calculateOrderTotals();
    toggleOrderChequeRefField();

    const form = document.getElementById('orderBookingForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const rows = document.querySelectorAll('.order-item-row');
            for (const row of rows) {
                const select = row.querySelector('.product-select');
                const qtyInput = row.querySelector('.qty-input');
                if (!select || !select.value) continue;
                const opt = select.options[select.selectedIndex];
                const stock = parseFloat(opt.getAttribute('data-stock') || 0);
                const unit = opt.getAttribute('data-unit') || 'pcs';
                const qty = parseInt(qtyInput ? qtyInput.value : 1);
                const prodName = opt.text.split(' - ')[0] || 'Selected product';

                if (stock <= 0) {
                    e.preventDefault();
                    alert(`Cannot book order: "${prodName}" is OUT OF STOCK! (Available: 0 ${unit}). Please select another item or enter stock first.`);
                    select.focus();
                    return false;
                }
                if (qty > stock) {
                    e.preventDefault();
                    alert(`Cannot book order: "${prodName}" requested quantity (${qty} ${unit}) exceeds available stock (${stock} ${unit}). Please adjust the quantity.`);
                    qtyInput.focus();
                    return false;
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
