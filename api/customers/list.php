<?php
// api/customers/list.php - Fetch Customer Directory & Live Credit Metrics
require_once __DIR__ . '/../helpers.php';

$authUser = authenticateApiUser();
$db = getDB();

try {
    $stmt = $db->query("
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
    ");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedList = [];
    foreach ($customers as $c) {
        $cLimit = floatval($c['credit_limit'] ?? 0);
        $cOutstanding = floatval($c['total_outstanding'] ?? 0);
        $cAvailable = max(0, $cLimit - $cOutstanding);
        $discBisc = floatval($c['discount_biscuits'] ?? $c['special_discount'] ?? 0);
        $discOther = floatval($c['discount_other'] ?? $c['special_discount'] ?? 0);

        $formattedList[] = [
            'id' => (int)$c['id'],
            'title' => $c['title'] ?? '',
            'name' => $c['name'],
            'full_display_name' => trim(($c['title'] ?? '') . ' ' . $c['name']),
            'phone' => $c['phone'] ?? '',
            'email' => $c['email'] ?? '',
            'address' => $c['address'] ?? '',
            'credit_limit' => $cLimit,
            'total_outstanding' => $cOutstanding,
            'available_credit' => $cAvailable,
            'discount_biscuits' => $discBisc,
            'discount_other' => $discOther
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($formattedList),
        'data' => $formattedList
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching customers: ' . $e->getMessage()
    ]);
}
