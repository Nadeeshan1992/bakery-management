<?php
// modules/pos/invoice.php - Invoice & Receipt View with Payment History
require_once __DIR__ . '/../../includes/header.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

$stmt = $db->prepare("SELECT o.*, c.name as customer_name, c.phone as customer_phone, u.full_name as cashier_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id LEFT JOIN users u ON o.created_by = u.id WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
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

// Fetch Payment History
$stmtPayments = $db->prepare("SELECT * FROM order_payments WHERE order_id = ? ORDER BY id ASC");
$stmtPayments->execute([$orderId]);
$paymentHistory = $stmtPayments->fetchAll();
$balDueInvoice = max(0, (float)$order['total_amount'] - (float)$order['paid_amount']);
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <!-- Print Trigger Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <a href="<?php echo BASE_URL; ?>modules/orders/index.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Orders
            </a>
            <button onclick="window.print()" class="btn btn-warning text-dark fw-bold px-4">
                <i class="fa-solid fa-print me-2"></i> Print Thermal Receipt
            </button>
        </div>

        <!-- Thermal Receipt Container -->
        <div id="printableReceipt" class="card card-bakery p-4 bg-white shadow-sm border">
            <!-- Receipt Header -->
            <div class="text-center mb-4 pb-3 border-bottom border-2 border-dashed">
                <img src="<?php echo BASE_URL; ?>assets/images/mlb_logo.jpg" alt="MLB Logo" class="mb-2" style="height: 70px;">
                <h3 class="fw-bold mb-0 text-dark">MLB POS SYSTEM</h3>
                <p class="text-muted mb-1 text-sm">Bakery & Confectionery</p>
                <p class="text-muted mb-0 text-sm">Tel: +94 77 123 4567 &bull; VAT: 22AAAAA0000A1Z5</p>
            </div>

            <!-- Receipt Info -->
            <div class="row mb-3 text-sm">
                <div class="col-6">
                    <span class="text-muted d-block">Invoice #:</span>
                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                </div>
                <div class="col-6 text-end">
                    <span class="text-muted d-block">Date & Time:</span>
                    <strong><?php echo formatDateTime($order['created_at']); ?></strong>
                </div>
                <div class="col-6 mt-2">
                    <span class="text-muted d-block">Customer:</span>
                    <strong><?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></strong>
                </div>
                <div class="col-6 mt-2 text-end">
                    <span class="text-muted d-block">Cashier:</span>
                    <strong><?php echo htmlspecialchars($order['cashier_name'] ?? 'Staff'); ?></strong>
                </div>
            </div>

            <!-- Purchased Itemized Table -->
            <table class="table table-sm table-borderless my-3 border-top border-bottom py-2">
                <thead>
                    <tr class="text-muted border-bottom" style="font-size: 0.85rem;">
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-end"><?php echo formatMoney($item['unit_price']); ?></td>
                            <td class="text-end fw-bold"><?php echo formatMoney($item['subtotal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Customer Returned Items (If Any) -->
            <?php if (!empty($returnedItems)): ?>
                <div class="mb-3 p-2 bg-light rounded border border-danger-subtle">
                    <div class="fw-bold text-danger mb-1 text-sm border-bottom pb-1">
                        <i class="fa-solid fa-arrow-rotate-left me-1"></i> Customer Returned Items:
                    </div>
                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.82rem;">
                        <thead>
                            <tr class="text-muted border-bottom" style="font-size: 0.75rem;">
                                <th>Returned Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($returnedItems as $rItem): 
                                $rReasonLabel = ($rItem['reason'] === 'expired') ? 'Expired' : 'Over-Order';
                            ?>
                                <tr class="text-danger">
                                    <td>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($rItem['product_name'] ?: 'Returned Item'); ?></span>
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">(<?php echo $rReasonLabel; ?>)</small>
                                    </td>
                                    <td class="text-center"><?php echo number_format($rItem['quantity'], 1); ?></td>
                                    <td class="text-end"><?php echo formatMoney($rItem['unit_price']); ?></td>
                                    <td class="text-end fw-bold">-<?php echo formatMoney($rItem['total_value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Calculation Summary -->
            <div class="row text-end my-2">
                <div class="col-7 text-muted">Subtotal:</div>
                <div class="col-5 fw-semibold"><?php echo formatMoney($order['subtotal']); ?></div>

                <?php if ($actualDiscount > 0): ?>
                    <div class="col-7 text-muted">Discount:</div>
                    <div class="col-5 text-danger">-<?php echo formatMoney($actualDiscount); ?></div>
                <?php endif; ?>

                <?php if ($totalReturnCredit > 0): ?>
                    <div class="col-7 text-muted">Returned Goods Credit:</div>
                    <div class="col-5 text-danger">-<?php echo formatMoney($totalReturnCredit); ?></div>
                <?php endif; ?>

                <div class="col-7 fw-bold text-dark fs-5 pt-2 border-top">Total Amount:</div>
                <div class="col-5 fw-bold text-success fs-5 pt-2 border-top"><?php echo formatMoney($order['total_amount']); ?></div>

                <!-- Payment Installments Breakdown -->
                <?php if (!empty($paymentHistory)): ?>
                    <?php foreach ($paymentHistory as $pay): ?>
                        <div class="col-7 text-muted pt-1">
                            <small class="text-capitalize"><?php echo htmlspecialchars($pay['payment_type']); ?></small> (<?php echo strtoupper(htmlspecialchars($pay['payment_method'])); ?>):
                        </div>
                        <div class="col-5 fw-semibold text-success pt-1">+ <?php echo formatMoney($pay['payment_amount']); ?></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-7 text-muted pt-2">Paid Amount:</div>
                    <div class="col-5 fw-semibold pt-2"><?php echo formatMoney($order['paid_amount']); ?></div>
                <?php endif; ?>

                <?php if ($balDueInvoice > 0): ?>
                    <div class="col-7 text-muted fw-bold pt-2 border-top">Balance Due:</div>
                    <div class="col-5 fw-bold text-danger pt-2 border-top"><?php echo formatMoney($balDueInvoice); ?></div>
                <?php else: ?>
                    <div class="col-7 text-muted pt-1">Change Due:</div>
                    <div class="col-5 fw-bold text-primary pt-1"><?php echo formatMoney($order['change_amount']); ?></div>
                <?php endif; ?>
            </div>

            <!-- Receipt Footer -->
            <div class="text-center mt-4 pt-3 border-top border-2 border-dashed text-muted" style="font-size: 0.85rem;">
                <p class="mb-1 fw-semibold text-dark">Thank you for visiting MLB POS System!</p>
                <p class="mb-0">Freshly Baked Daily with Love & Pure Butter ❤️</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
