<?php
// api/orders/list.php - Fetch Mobile Pre-Orders List
require_once __DIR__ . '/../helpers.php';

$authUser = authenticateApiUser();
$db = getDB();

$whereClauses = [];
$params = [];

if ($authUser['role'] === 'sales_person') {
    $whereClauses[] = "o.created_by = ?";
    $params[] = $authUser['id'];
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

try {
    $stmt = $db->prepare("
        SELECT o.*, c.name as customer_name, c.phone as customer_phone 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        $whereSql 
        ORDER BY o.id DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($orders as $o) {
        $balDue = max(0, floatval($o['total_amount']) - floatval($o['paid_amount']));
        $formatted[] = [
            'id' => (int)$o['id'],
            'order_number' => $o['order_number'],
            'customer_name' => $o['customer_name'] ?? 'Walk-in',
            'customer_phone' => $o['customer_phone'] ?? '',
            'order_type' => $o['order_type'],
            'total_amount' => floatval($o['total_amount']),
            'paid_amount' => floatval($o['paid_amount']),
            'balance_due' => $balDue,
            'payment_method' => $o['payment_method'],
            'cheque_ref' => $o['cheque_ref'] ?? '',
            'payment_status' => $o['payment_status'],
            'order_status' => $o['order_status'],
            'delivery_date' => $o['delivery_date'] ?? '',
            'created_at' => $o['created_at']
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($formatted),
        'data' => $formatted
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching orders: ' . $e->getMessage()
    ]);
}
