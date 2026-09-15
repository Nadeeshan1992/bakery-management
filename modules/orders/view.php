<?php
// modules/orders/view.php - Detailed Order Overview, Status Updating & Payment Settlement
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

$stmt = $db->prepare("SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address, u.full_name as created_by_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id LEFT JOIN users u ON o.created_by = u.id WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    header('Location: ' . BASE_URL . 'modules/orders/index.php');
    exit;
}

if (getCurrentUserRole() === 'sales_person' && intval($order['created_by'] ?? 0) !== getCurrentUserId()) {
    setFlash('error', 'Access denied: You can only view your own pre-orders.');
    header('Location: ' . BASE_URL . 'modules/orders/index.php');
    exit;
}

$stmtItems = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

// Fetch Customer Returned Items if any
$stmtReturns = $db->prepare("
    SELECT r.*, p.name as product_name 
    FROM order_returns r 
    LEFT JOIN products p ON r.product_id = p.id 
    WHERE r.order_id = ?
");
$stmtReturns->execute([$orderId]);
$returnedItems = $stmtReturns->fetchAll();

$totalReturnCredit = 0;
foreach ($returnedItems as $rItem) {
    $totalReturnCredit += (float)$rItem['total_value'];
}
$actualDiscount = max(0, (float)$order['discount'] - $totalReturnCredit);

// Fetch payment history
$stmtPayments = $db->prepare("
    SELECT op.*, u.full_name as cashier_name 
    FROM order_payments op 
    LEFT JOIN users u ON op.created_by = u.id 
    WHERE op.order_id = ? 
    ORDER BY op.id ASC
");
$stmtPayments->execute([$orderId]);
$paymentHistory = $stmtPayments->fetchAll();

$remainingBalance = max(0, (float)$order['total_amount'] - (float)$order['paid_amount']);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Order #: <?php echo htmlspecialchars($order['order_number']); ?></h4>
        <p class="text-muted mb-0">Booked on <?php echo formatDateTime($order['created_at']); ?> by <?php echo htmlspecialchars($order['created_by_name']); ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>modules/orders/index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Orders
        </a>
        <a href="<?php echo BASE_URL; ?>modules/orders/edit.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-pen-to-square me-1"></i> Edit Bill / Order
        </a>
        <a href="<?php echo BASE_URL; ?>modules/pos/invoice.php?id=<?php echo $order['id']; ?>" class="btn btn-warning text-dark fw-bold" target="_blank">
            <i class="fa-solid fa-print me-1"></i> Print Invoice
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Main Order Details -->
    <div class="col-lg-8">
        <!-- Order Items -->
        <div class="card card-bakery p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-basket-shopping text-warning me-2"></i> Items Summary</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item Name</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><strong class="text-dark"><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end"><?php echo formatMoney($item['unit_price']); ?></td>
                                <td class="text-end fw-bold"><?php echo formatMoney($item['subtotal']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Customer Returned Goods Section (if any) -->
            <?php if (!empty($returnedItems)): ?>
                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold text-danger mb-2"><i class="fa-solid fa-arrow-rotate-left me-1"></i> Customer Returned Goods (Credit Deductions)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-danger text-danger">
                                <tr>
                                    <th>Returned Item</th>
                                    <th>Reason</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Credit Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($returnedItems as $rItem): 
                                    $rReasonLabel = ($rItem['reason'] === 'expired') ? '<span class="badge bg-danger">Expired/Spoilage</span>' : '<span class="badge bg-info text-dark">Over Order</span>';
                                ?>
                                    <tr>
                                        <td><strong class="text-dark"><?php echo htmlspecialchars($rItem['product_name'] ?: 'Returned Item'); ?></strong></td>
                                        <td><?php echo $rReasonLabel; ?></td>
                                        <td class="text-center"><?php echo number_format($rItem['quantity'], 1); ?></td>
                                        <td class="text-end"><?php echo formatMoney($rItem['unit_price']); ?></td>
                                        <td class="text-end fw-bold text-danger">-<?php echo formatMoney($rItem['total_value']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row text-end mt-2">
                <div class="col-7 text-muted">Subtotal:</div>
                <div class="col-5 fw-semibold"><?php echo formatMoney($order['subtotal']); ?></div>

                <?php if ($actualDiscount > 0): ?>
                    <div class="col-7 text-muted">Customer Discount:</div>
                    <div class="col-5 text-danger">-<?php echo formatMoney($actualDiscount); ?></div>
                <?php endif; ?>

                <?php if ($totalReturnCredit > 0): ?>
                    <div class="col-7 text-muted">Returned Goods Credit:</div>
                    <div class="col-5 text-danger">-<?php echo formatMoney($totalReturnCredit); ?></div>
                <?php endif; ?>

                <div class="col-7 fw-bold text-dark fs-5 pt-2 border-top">Grand Total:</div>
                <div class="col-5 fw-bold text-success fs-5 pt-2 border-top"><?php echo formatMoney($order['total_amount']); ?></div>

                <div class="col-7 text-muted pt-2">Total Paid So Far:</div>
                <div class="col-5 fw-semibold text-success pt-2"><?php echo formatMoney($order['paid_amount']); ?></div>

                <div class="col-7 text-muted">Remaining Balance Due:</div>
                <div class="col-5 fw-bold <?php echo $remainingBalance > 0 ? 'text-danger' : 'text-success'; ?>">
                    <?php echo formatMoney($remainingBalance); ?>
                </div>
            </div>
        </div>

        <!-- Payment History Audit Log -->
        <div class="card card-bakery p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i> Payment Transactions & History</h5>
                <button type="button" class="btn btn-sm btn-outline-success fw-bold" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Payment
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Time</th>
                            <th>Payment Type</th>
                            <th>Method</th>
                            <th>Collector</th>
                            <th class="text-end">Amount Paid</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paymentHistory)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">No payment records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($paymentHistory as $pay): ?>
                                <tr>
                                    <td><small><?php echo formatDateTime($pay['created_at']); ?></small></td>
                                    <td>
                                        <span class="badge bg-light text-dark border text-capitalize">
                                            <?php echo htmlspecialchars($pay['payment_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($pay['payment_method']); ?></span>
                                        <?php if (!empty($pay['cheque_ref'])): ?>
                                            <small class="d-block text-primary fw-bold text-nowrap mt-1" style="font-size:0.75rem;">
                                                <i class="fa-solid fa-money-check me-1"></i>Ref: <?php echo htmlspecialchars($pay['cheque_ref']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-dark fw-semibold"><?php echo htmlspecialchars($pay['cashier_name'] ?: 'Staff'); ?></small></td>
                                    <td class="text-end fw-bold text-success">+ <?php echo formatMoney($pay['payment_amount']); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($pay['notes'] ?: '-'); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Custom Notes & Specifications -->
        <?php if (!empty($order['custom_notes'])): ?>
            <div class="card card-bakery p-4">
                <h5 class="fw-bold mb-2"><i class="fa-solid fa-file-pen text-warning me-2"></i> Pre-Order Specifications & Notes</h5>
                <div class="p-3 bg-light rounded border text-dark">
                    <?php echo nl2br(htmlspecialchars($order['custom_notes'])); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Sidebar: Status & Customer Info -->
    <div class="col-lg-4">
        <!-- Payment Settlement Quick Action Card -->
        <div class="card card-bakery p-4 mb-4 border-start border-4 <?php echo $remainingBalance > 0 ? 'border-danger' : 'border-success'; ?>">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-receipt text-success me-2"></i> Payment Settlement</h5>
            
            <div class="mb-3 p-3 bg-light rounded border text-center">
                <small class="text-muted d-block text-uppercase fw-bold">Remaining Balance Due</small>
                <?php if ($remainingBalance > 0): ?>
                    <div class="fs-3 fw-bold text-danger">Rs. <?php echo number_format($remainingBalance, 2); ?></div>
                    <span class="badge bg-warning text-dark mt-1">Pending Balance Payment</span>
                <?php else: ?>
                    <div class="fs-3 fw-bold text-success">Rs. 0.00</div>
                    <span class="badge bg-success mt-1"><i class="fa-solid fa-check-double me-1"></i> FULLY SETTLED</span>
                <?php endif; ?>
            </div>

            <?php if ($remainingBalance > 0): ?>
                <button type="button" class="btn btn-success text-white fw-bold w-100 py-2 shadow-sm mb-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="fa-solid fa-hand-holding-dollar me-2"></i> Collect Payment / Settle Balance
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-outline-success fw-bold w-100 py-2 mb-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="fa-solid fa-plus me-1"></i> Record Additional Payment
                </button>
            <?php endif; ?>
        </div>

        <!-- Status Update Card -->
        <div class="card card-bakery p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-sliders text-warning me-2"></i> Order Status</h5>
            
            <div class="mb-3">
                <span class="text-muted d-block text-xs">Current Status:</span>
                <div class="mt-1"><?php echo getStatusBadge($order['order_status']); ?></div>
            </div>

            <form action="<?php echo BASE_URL; ?>modules/orders/update_status.php" method="POST">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Change Workflow Status</label>
                    <select name="order_status" class="form-select">
                        <option value="pending" <?php echo ($order['order_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_production" <?php echo ($order['order_status'] == 'in_production') ? 'selected' : ''; ?>>In Production (Baking)</option>
                        <option value="ready" <?php echo ($order['order_status'] == 'ready') ? 'selected' : ''; ?>>Ready for Pickup/Delivery</option>
                        <option value="completed" <?php echo ($order['order_status'] == 'completed') ? 'selected' : ''; ?>>Completed & Delivered</option>
                        <option value="cancelled" <?php echo ($order['order_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold">Payment Status</label>
                    <select name="payment_status" class="form-select">
                        <option value="unpaid" <?php echo ($order['payment_status'] == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                        <option value="partial" <?php echo ($order['payment_status'] == 'partial') ? 'selected' : ''; ?>>Partial Advance</option>
                        <option value="paid" <?php echo ($order['payment_status'] == 'paid') ? 'selected' : ''; ?>>Fully Paid</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-warning text-dark fw-bold w-100">
                    <i class="fa-solid fa-rotate me-1"></i> Update Order Status
                </button>
            </form>
        </div>

        <!-- Customer & Delivery Info -->
        <div class="card card-bakery p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-user-tag text-warning me-2"></i> Delivery Details</h5>
            <ul class="list-unstyled mb-0 text-sm">
                <li class="mb-2">
                    <strong class="d-block text-dark">Customer Name:</strong>
                    <span><?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></span>
                </li>
                <li class="mb-2">
                    <strong class="d-block text-dark">Contact Phone:</strong>
                    <span><?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></span>
                </li>
                <li class="mb-2">
                    <strong class="d-block text-dark">Delivery Date & Time:</strong>
                    <span class="text-primary fw-semibold"><?php echo formatDateTime($order['delivery_date']); ?></span>
                </li>
                <li>
                    <strong class="d-block text-dark">Delivery Address:</strong>
                    <span><?php echo htmlspecialchars($order['customer_address'] ?? 'Store Pickup'); ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Collect / Record Payment Modal -->
<div class="modal fade text-start" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?php echo BASE_URL; ?>modules/orders/add_payment.php" method="POST">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-hand-holding-dollar text-success me-2"></i> Record Settlement / Partial Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between text-sm mb-1">
                            <span class="text-muted">Total Order Amount:</span>
                            <strong><?php echo formatMoney($order['total_amount']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between text-sm mb-1">
                            <span class="text-muted">Total Paid So Far:</span>
                            <strong class="text-success"><?php echo formatMoney($order['paid_amount']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between text-sm pt-2 border-top">
                            <span class="fw-bold text-dark">Remaining Balance Due:</span>
                            <strong class="text-danger fs-6"><?php echo formatMoney($remainingBalance); ?></strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Payment Amount (LKR):</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="any" min="0.01" id="paymentAmountInput" name="payment_amount" class="form-control fw-bold text-success fs-4" value="<?php echo $remainingBalance > 0 ? number_format($remainingBalance, 2, '.', '') : ''; ?>" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Payment Method:</label>
                            <select name="payment_method" id="settlePaymentMethodSelect" class="form-select" onchange="toggleSettleChequeRefField()">
                                <option value="cash" selected>Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online Transfer</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Payment Type:</label>
                            <select name="payment_type" class="form-select">
                                <option value="settlement" <?php echo ($remainingBalance > 0) ? 'selected' : ''; ?>>Final Settlement</option>
                                <option value="installment">Partial Installment</option>
                                <option value="advance">Advance Top-up</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3" id="settleChequeRefContainer" style="display: none;">
                        <label class="form-label font-weight-bold text-primary">
                            <i class="fa-solid fa-money-check me-1"></i> Cheque Reference No. <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="cheque_ref" id="settleChequeRefInput" class="form-control fw-bold border-primary" placeholder="Enter cheque number (e.g. CHQ-984712)">
                        <small class="text-muted">Enter cheque number or reference details.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Payment Notes / Reference:</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Settlement on customer pickup" value="<?php echo $remainingBalance > 0 ? 'Full balance settlement on pickup' : 'Additional payment'; ?>">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="fa-solid fa-check me-1"></i> Save & Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSettleChequeRefField() {
    const select = document.getElementById('settlePaymentMethodSelect');
    const container = document.getElementById('settleChequeRefContainer');
    const input = document.getElementById('settleChequeRefInput');
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
    toggleSettleChequeRefField();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
