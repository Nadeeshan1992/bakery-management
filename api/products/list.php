<?php
// api/products/list.php - Fetch Product Catalog & Live Stock
require_once __DIR__ . '/../helpers.php';

$authUser = authenticateApiUser();
$db = getDB();

try {
    $categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

    $stmtP = $db->query("
        SELECT p.id, p.category_id, p.name, p.sku, p.price, p.price_retail, p.current_stock, p.status, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active' 
        ORDER BY c.name ASC, p.name ASC
    ");
    $products = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    $formattedProducts = [];
    foreach ($products as $p) {
        $effectivePrice = floatval(($p['price_retail'] > 0) ? $p['price_retail'] : $p['price']);
        $formattedProducts[] = [
            'id' => (int)$p['id'],
            'category_id' => (int)$p['category_id'],
            'category_name' => $p['category_name'] ?? 'General',
            'name' => $p['name'],
            'sku' => $p['sku'] ?? '',
            'price_cost' => floatval($p['price']),
            'price_retail' => floatval($p['price_retail']),
            'effective_price' => $effectivePrice,
            'current_stock' => floatval($p['current_stock'])
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'count' => count($formattedProducts),
        'data' => $formattedProducts
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching products: ' . $e->getMessage()
    ]);
}
