<?php
// api/orders/create.php - Mobile Pre-Order Booking Endpoint
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../includes/sms_helper.php';

$authUser = authenticateApiUser();
$db = getDB();

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (empty($input) && !empty($_POST)) {
    $input = $_POST;
}

$customerId = intval($input['customer_id'] ?? 0);
$deliveryDate = !empty($input['delivery_date']) ? $input['delivery_date'] : NULL;
$customNotes = trim($input['custom_notes'] ?? '');
$items = $input['items'] ?? [];
$discount = floatval($input['discount'] ?? 0);
$paidAmount = floatval($input['paid_amount'] ?? 0);
$paymentMethod = $input['payment_method'] ?? 'cash';
$chequeRef = trim($input['cheque_ref'] ?? '');
$returnItems = $input['returned_items'] ?? [];

if ($customerId <= 0 || empty($items) || !is_array($items)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please select a valid customer and at least one order item.'
    ]);
    exit;
}

try {
    $db->beginTransaction();

    $subtotal = 0;
    $itemsToInsert = [];

    foreach ($items as $item) {
        $prodId = intval($item['product_id'] ?? $item['id'] ?? 0);
        $qty = intval($item['quantity'] ?? $item['qty'] ?? 1);

        if ($prodId > 0 && $qty > 0) {
            $stmtP = $db->prepare("SELECT name, price_retail, price, current_stock FROM products WHERE id = ?");
            $stmtP->execute([$prodId]);
            $pData = $stmtP->fetch(PDO::FETCH_ASSOC);

            if ($pData) {
                if (floatval($pData['current_stock']) < $qty) {
                    $avail = intval($pData['current_stock']);
                    throw new Exception('Cannot book order: "' . $pData['name'] . '" is OUT OF STOCK! (Available: ' . $avail . ' pcs, Requested: ' . $qty . ' pcs).');
                }
                $unitPrice = floatval(($pData['price_retail'] > 0) ? $pData['price_retail'] : $pData['price']);
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

    // Returned Goods Financial Credit
    $totalReturnCredit = 0;
    $returnsToInsert = [];
    if (!empty($returnItems) && is_array($returnItems)) {
        foreach ($returnItems as $rItem) {
            $rProdId = intval($rItem['product_id'] ?? 0);
            $rQty = floatval($rItem['quantity'] ?? 0);
            $rReason = $rItem['reason'] ?? 'over_order';

            if ($rProdId > 0 && $rQty > 0) {
                $stmtRP = $db->prepare("SELECT name, price_retail, price FROM products WHERE id = ?");
                $stmtRP->execute([$rProdId]);
                $rPData = $stmtRP->fetch(PDO::FETCH_ASSOC);

                if ($rPData) {
                    $rUnitPrice = floatval(($rPData['price_retail'] > 0) ? $rPData['price_retail'] : $rPData['price']);
                    $rTotalVal = $rUnitPrice * $rQty;
                    $totalReturnCredit += $rTotalVal;
                    $returnsToInsert[] = [
                        'product_id' => $rProdId,
                        'name' => $rPData['name'],
                        'qty' => $rQty,
                        'unit_price' => $rUnitPrice,
                        'total_val' => $rTotalVal,
                        'reason' => $rReason
                    ];
                }
            }
        }
    }

    $totalAmount = max(0, $subtotal - $discount - $totalReturnCredit);
    $changeAmount = max(0, $paidAmount - $totalAmount);
    $actualPaidAmount = min($paidAmount, $totalAmount);
    $balanceDue = max(0, $totalAmount - $actualPaidAmount);
    $paymentStatus = ($actualPaidAmount >= $totalAmount) ? 'paid' : (($actualPaidAmount > 0) ? 'partial' : 'unpaid');
    $orderStatus = ($paymentStatus === 'paid') ? 'completed' : 'pending';

    // Credit Limit Verification
    $stmtCustCheck = $db->prepare("SELECT credit_limit, opening_balance FROM customers WHERE id = ?");
    $stmtCustCheck->execute([$customerId]);
    $cCheckData = $stmtCustCheck->fetch(PDO::FETCH_ASSOC);

    if ($cCheckData) {
        $creditLimitCheck = floatval($cCheckData['credit_limit'] ?? 0);
        if ($creditLimitCheck > 0) {
            $stmtUnpaid = $db->prepare("SELECT COALESCE(SUM(total_amount - paid_amount), 0) as unpaid_sum FROM orders WHERE customer_id = ? AND order_status != 'cancelled' AND payment_status != 'paid'");
            $stmtUnpaid->execute([$customerId]);
            $unpaidSum = floatval($stmtUnpaid->fetch()['unpaid_sum'] ?? 0);
            $currentOutstanding = floatval($cCheckData['opening_balance'] ?? 0) + $unpaidSum;
            $availableCredit = max(0, $creditLimitCheck - $currentOutstanding);

            if ($balanceDue > $availableCredit) {
                throw new Exception('Order exceeds customer credit limit! Balance Due: Rs. ' . number_format($balanceDue, 2) . ', Available Credit: Rs. ' . number_format($availableCredit, 2) . '.');
            }
        }
    }

    $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

    $stmtOrder = $db->prepare("
        INSERT INTO orders (order_number, customer_id, order_type, subtotal, discount, tax, total_amount, paid_amount, change_amount, payment_method, cheque_ref, payment_status, order_status, delivery_date, custom_notes, created_by) 
        VALUES (?, ?, 'preorder', ?, ?, 0.00, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtOrder->execute([
        $orderNumber,
        $customerId,
        $subtotal,
        ($discount + $totalReturnCredit),
        $totalAmount,
        $actualPaidAmount,
        $changeAmount,
        $paymentMethod,
        (!empty($chequeRef) ? $chequeRef : NULL),
        $paymentStatus,
        $orderStatus,
        $deliveryDate,
        $customNotes,
        $authUser['id']
    ]);

    $orderId = $db->lastInsertId();

    // Insert Order Items & Deduct Stock
    $stmtItem = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtDeductStock = $db->prepare("UPDATE products SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?");
    $stmtStockLog = $db->prepare("INSERT INTO product_stock_logs (product_id, log_type, quantity, reference_order_id, notes, created_by) VALUES (?, 'sale_deduction', ?, ?, ?, ?)");

    foreach ($itemsToInsert as $it) {
        $stmtItem->execute([$orderId, $it['product_id'], $it['name'], $it['qty'], $it['price'], $it['subtotal']]);
        if ($it['product_id'] > 0 && $it['qty'] > 0) {
            $stmtDeductStock->execute([$it['qty'], $it['product_id']]);
            $stmtStockLog->execute([$it['product_id'], $it['qty'], $orderId, 'Mobile Pre-Order #' . $orderNumber, $authUser['id']]);
        }
    }

    // Advance Payment Log
    if ($paidAmount > 0) {
        $stmtPayInit = $db->prepare("INSERT INTO order_payments (order_id, payment_amount, payment_method, cheque_ref, payment_type, notes, created_by) VALUES (?, ?, ?, ?, 'advance', ?, ?)");
        $stmtPayInit->execute([
            $orderId,
            $paidAmount,
            $paymentMethod,
            (!empty($chequeRef) ? $chequeRef : NULL),
            'Mobile app advance deposit on pre-order booking',
            $authUser['id']
        ]);
    }

    // Process Returned Items
    $stmtReturn = $db->prepare("INSERT INTO order_returns (order_id, customer_id, product_id, quantity, unit_price, total_value, reason, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtRestock = $db->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
    $stmtStockLogReturn = $db->prepare("INSERT INTO product_stock_logs (product_id, log_type, quantity, reference_order_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($returnsToInsert as $rIt) {
        $stmtReturn->execute([$orderId, $customerId, $rIt['product_id'], $rIt['qty'], $rIt['unit_price'], $rIt['total_val'], $rIt['reason'], $authUser['id']]);
        if ($rIt['reason'] === 'over_order') {
            $stmtRestock->execute([$rIt['qty'], $rIt['product_id']]);
            $stmtStockLogReturn->execute([$rIt['product_id'], 'adjustment', $rIt['qty'], $orderId, 'Customer Over-Order Return restocked via mobile app on #' . $orderNumber, $authUser['id']]);
        } else {
            $stmtStockLogReturn->execute([$rIt['product_id'], 'waste', $rIt['qty'], $orderId, 'Customer Expired Goods written off via mobile app on #' . $orderNumber, $authUser['id']]);
        }
    }

    $db->commit();

    // Trigger automated customer SMS confirmation
    try {
        sendOrderConfirmationSMS($orderId, $db);
    } catch (Exception $e) {
        error_log("Failed to send mobile preorder SMS: " . $e->getMessage());
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Pre-Order #' . $orderNumber . ' created successfully!',
        'data' => [
            'order_id' => (int)$orderId,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'balance_due' => $balanceDue,
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus
        ]
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
