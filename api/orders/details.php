<?php
// api/orders/details.php - Fetch Mobile Pre-Order Details
require_once __DIR__ . '/../helpers.php';

$authUser = authenticateApiUser();
$db = getDB();

$orderId = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Valid order ID required.'
    ]);
    exit;
}

try {
    $stmtO = $db->prepare("
        SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, u.full_name as created_by_name 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        LEFT JOIN users u ON o.created_by = u.id 
        WHERE o.id = ?
    ");
    $stmtO->execute([$orderId]);
    $order = $stmtO->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Order not found.'
        ]);
        exit;
    }

    if ($authUser['role'] === 'sales_person' && intval($order['created_by']) !== intval($authUser['id'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied: You can only view your own pre-orders.'
        ]);
        exit;
    }

    // Items
    $stmtItems = $db->prepare("SELECT id, product_id, product_name, quantity, unit_price, subtotal FROM order_items WHERE order_id = ?");
    $stmtItems->execute([$orderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Payments
    $stmtPay = $db->prepare("SELECT op.*, u.full_name as cashier_name FROM order_payments op LEFT JOIN users u ON op.created_by = u.id WHERE op.order_id = ? ORDER BY op.id ASC");
    $stmtPay->execute([$orderId]);
    $payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

    // Returns
    $stmtRet = $db->prepare("SELECT r.*, p.name as product_name FROM order_returns r LEFT JOIN products p ON r.product_id = p.id WHERE r.order_id = ?");
    $stmtRet->execute([$orderId]);
    $returns = $stmtRet->fetchAll(PDO::FETCH_ASSOC);

    $balDue = max(0, floatval($order['total_amount']) - floatval($order['paid_amount']));

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'order' => [
                'id' => (int)$order['id'],
                'order_number' => $order['order_number'],
                'customer_name' => $order['customer_name'] ?? 'Walk-in',
                'customer_phone' => $order['customer_phone'] ?? '',
                'customer_address' => $order['customer_address'] ?? '',
                'created_by_name' => $order['created_by_name'] ?? '',
                'subtotal' => floatval($order['subtotal']),
                'discount' => floatval($order['discount']),
                'total_amount' => floatval($order['total_amount']),
                'paid_amount' => floatval($order['paid_amount']),
                'balance_due' => $balDue,
                'payment_method' => $order['payment_method'],
                'cheque_ref' => $order['cheque_ref'] ?? '',
                'payment_status' => $order['payment_status'],
                'order_status' => $order['order_status'],
                'delivery_date' => $order['delivery_date'] ?? '',
                'custom_notes' => $order['custom_notes'] ?? '',
                'created_at' => $order['created_at']
            ],
            'items' => $items,
            'payments' => $payments,
            'returns' => $returns
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching order details: ' . $e->getMessage()
    ]);
}
