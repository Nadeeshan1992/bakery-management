<?php
// api/reports/sales.php - Sales Person Performance & Sales Report API
require_once __DIR__ . '/../helpers.php';

$authUser = authenticateApiUser();
$db = getDB();

$dateFrom = !empty($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$dateTo = !empty($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

$targetUserId = $authUser['id'];

// Admin / Owner can optionally view reports for specific sales persons
if (($authUser['role'] === 'admin' || $authUser['role'] === 'owner') && isset($_GET['sales_person_id']) && $_GET['sales_person_id'] !== 'all') {
    $targetUserId = intval($_GET['sales_person_id']);
}

$userWhere = "WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.order_status != 'cancelled'";
$params = [$dateFrom, $dateTo];

if ($authUser['role'] === 'sales_person' || $targetUserId > 0) {
    $userWhere .= " AND o.created_by = ?";
    $params[] = $targetUserId;
}

try {
    // 1. Overall Financial Summary
    $stmtSum = $db->prepare("
        SELECT 
            COUNT(*) as total_orders_count,
            COALESCE(SUM(o.total_amount), 0) as net_sales,
            COALESCE(SUM(CASE WHEN o.order_type = 'pos' THEN o.total_amount ELSE 0 END), 0) as pos_sales_value,
            COALESCE(SUM(CASE WHEN o.order_type = 'preorder' THEN o.total_amount ELSE 0 END), 0) as preorder_sales_value,
            COALESCE(SUM(o.paid_amount), 0) as total_collected
        FROM orders o 
        {$userWhere}
    ");
    $stmtSum->execute($params);
    $sum = $stmtSum->fetch(PDO::FETCH_ASSOC);

    $totalOrdersCount = (int)($sum['total_orders_count'] ?? 0);
    $posSalesValue = floatval($sum['pos_sales_value'] ?? 0);
    $preorderSalesValue = floatval($sum['preorder_sales_value'] ?? 0);
    $netSales = floatval($sum['net_sales'] ?? 0);
    $totalCollected = floatval($sum['total_collected'] ?? 0);
    $settlementValue = $totalCollected;
    $totalNetRevenue = $posSalesValue + $settlementValue;
    $pendingAmount = $settlementValue - $preorderSalesValue;
    $totalOutstanding = max(0.00, $netSales - $totalCollected);

    // 2. Payment Method Breakdown
    $stmtPay = $db->prepare("
        SELECT o.payment_method, COUNT(*) as cnt, COALESCE(SUM(o.total_amount), 0) as amt, COALESCE(SUM(o.paid_amount), 0) as paid_amt 
        FROM orders o 
        {$userWhere} 
        GROUP BY o.payment_method
    ");
    $stmtPay->execute($params);
    $payRows = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

    $paymentBreakdown = [
        'cash' => ['count' => 0, 'total' => 0.0, 'collected' => 0.0],
        'cheque' => ['count' => 0, 'total' => 0.0, 'collected' => 0.0],
        'online' => ['count' => 0, 'total' => 0.0, 'collected' => 0.0]
    ];
    foreach ($payRows as $pr) {
        $m = strtolower($pr['payment_method']);
        if (isset($paymentBreakdown[$m])) {
            $paymentBreakdown[$m] = [
                'count' => (int)$pr['cnt'],
                'total' => floatval($pr['amt']),
                'collected' => floatval($pr['paid_amt'])
            ];
        }
    }

    // 3. Top Selling Products
    $stmtTop = $db->prepare("
        SELECT oi.product_name, SUM(oi.quantity) as total_qty, SUM(oi.subtotal) as total_sales 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        {$userWhere} 
        GROUP BY oi.product_id, oi.product_name 
        ORDER BY total_qty DESC 
        LIMIT 5
    ");
    $stmtTop->execute($params);
    $topProductsRaw = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

    $topProducts = [];
    foreach ($topProductsRaw as $tp) {
        $topProducts[] = [
            'product_name' => $tp['product_name'],
            'total_qty' => (int)$tp['total_qty'],
            'total_sales' => floatval($tp['total_sales'])
        ];
    }

    // 4. Detailed Orders Log for the Period
    $stmtList = $db->prepare("
        SELECT o.*, c.name as customer_name, c.phone as customer_phone 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        {$userWhere} 
        ORDER BY o.id DESC
    ");
    $stmtList->execute($params);
    $ordersRaw = $stmtList->fetchAll(PDO::FETCH_ASSOC);

    $ordersLog = [];
    foreach ($ordersRaw as $o) {
        $ordersLog[] = [
            'id' => (int)$o['id'],
            'order_number' => $o['order_number'],
            'customer_name' => $o['customer_name'] ?? 'Walk-in',
            'customer_phone' => $o['customer_phone'] ?? '',
            'order_type' => $o['order_type'],
            'total_amount' => floatval($o['total_amount']),
            'paid_amount' => floatval($o['paid_amount']),
            'balance_due' => max(0.00, floatval($o['total_amount']) - floatval($o['paid_amount'])),
            'payment_method' => $o['payment_method'],
            'cheque_ref' => $o['cheque_ref'] ?? '',
            'payment_status' => $o['payment_status'],
            'order_status' => $o['order_status'],
            'created_at' => $o['created_at']
        ];
    }

    send_json_response([
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'sales_person' => [
            'user_id' => $authUser['id'],
            'full_name' => $authUser['full_name'],
            'username' => $authUser['username'],
            'role' => $authUser['role']
        ],
        'summary' => [
            'total_orders_count'   => $totalOrdersCount,
            'pos_sales_value'      => $posSalesValue,
            'preorder_sales_value' => $preorderSalesValue,
            'settlement_value'     => $settlementValue,
            'total_net_revenue'    => $totalNetRevenue,
            'pending_amount'       => $pendingAmount,
            'net_sales'            => $netSales,
            'total_outstanding'    => $totalOutstanding
        ],
        'payment_breakdown' => $paymentBreakdown,
        'top_products' => $topProducts,
        'orders' => $ordersLog
    ], 'Sales report loaded successfully.');

} catch (Exception $e) {
    send_json_error('Error generating sales report: ' . $e->getMessage(), 500);
}
