<?php
// api/orders/settle.php - Settle Outstanding Pre-Order Payment
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../includes/sms_helper.php';

$authUser = authenticateApiUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_error('Invalid request method. POST required.', 405);
}

$input = get_json_input();
$orderId = intval($input['order_id'] ?? 0);
$paymentAmount = floatval($input['payment_amount'] ?? 0);
$paymentMethod = trim($input['payment_method'] ?? 'cash');
$chequeRef = trim($input['cheque_ref'] ?? '');
$notes = trim($input['notes'] ?? 'Mobile pre-order settlement payment');

if ($orderId <= 0) {
    send_json_error('Invalid or missing order_id.');
}

if ($paymentAmount <= 0) {
    send_json_error('Payment amount must be greater than zero.');
}

if ($paymentMethod === 'cheque' && empty($chequeRef)) {
    send_json_error('Cheque Reference Number is required for cheque payments.');
}

try {
    $db->beginTransaction();

    $stmtO = $db->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
    $stmtO->execute([$orderId]);
    $order = $stmtO->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $db->rollBack();
        send_json_error('Order not found.', 404);
    }

    // Role check: sales_person can only settle their own created orders
    if ($authUser['role'] === 'sales_person' && intval($order['created_by']) !== intval($authUser['id'])) {
        $db->rollBack();
        send_json_error('Unauthorized. You can only settle pre-orders created by you.', 403);
    }

    $currentPaid = floatval($order['paid_amount']);
    $totalAmount = floatval($order['total_amount']);
    $newPaidAmount = $currentPaid + $paymentAmount;

    $newPaymentStatus = ($newPaidAmount >= $totalAmount) ? 'paid' : 'partial';
    $changeAmount = max(0.00, $newPaidAmount - $totalAmount);
    $newOrderStatus = ($newPaymentStatus === 'paid') ? 'completed' : $order['order_status'];
    $finalChequeRef = (!empty($chequeRef)) ? $chequeRef : ($order['cheque_ref'] ?? NULL);

    $stmtUpdate = $db->prepare("
        UPDATE orders 
        SET paid_amount = ?, change_amount = ?, payment_status = ?, order_status = ?, payment_method = ?, cheque_ref = ? 
        WHERE id = ?
    ");
    $stmtUpdate->execute([
        $newPaidAmount,
        $changeAmount,
        $newPaymentStatus,
        $newOrderStatus,
        $paymentMethod,
        $finalChequeRef,
        $orderId
    ]);

    // Insert into order_payments
    $stmtPayment = $db->prepare("
        INSERT INTO order_payments (order_id, payment_amount, payment_method, cheque_ref, payment_type, notes, created_by) 
        VALUES (?, ?, ?, ?, 'settlement', ?, ?)
    ");
    $stmtPayment->execute([
        $orderId,
        $paymentAmount,
        $paymentMethod,
        (!empty($chequeRef) ? $chequeRef : NULL),
        $notes,
        $authUser['id']
    ]);

    $db->commit();

    // Trigger automated customer SMS receipt
    try {
        sendPaymentReceiptSMS($orderId, $paymentAmount, $paymentMethod, $db, $authUser['id']);
    } catch (Exception $e) {
        error_log("Failed to send settlement SMS: " . $e->getMessage());
    }

    $newBalanceDue = max(0.00, $totalAmount - $newPaidAmount);

    send_json_response([
        'order_id' => $orderId,
        'order_number' => $order['order_number'],
        'settled_amount' => $paymentAmount,
        'total_amount' => $totalAmount,
        'paid_amount' => $newPaidAmount,
        'balance_due' => $newBalanceDue,
        'payment_status' => $newPaymentStatus,
        'order_status' => $newOrderStatus,
        'payment_method' => $paymentMethod,
        'cheque_ref' => $finalChequeRef
    ], "Payment of Rs. " . number_format($paymentAmount, 2) . " settled successfully for Order #" . $order['order_number'] . "!");

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    send_json_error('Settlement error: ' . $e->getMessage(), 500);
}
