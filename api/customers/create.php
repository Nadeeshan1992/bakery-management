<?php
// api/customers/create.php - Register New Customer via Mobile API
require_once __DIR__ . '/../helpers.php';

$authUser = authenticateApiUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_error('Invalid request method. POST required.', 405);
}

$input = get_json_input();

$title = trim($input['title'] ?? 'Mr.');
$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '');
$email = trim($input['email'] ?? '');
$address = trim($input['address'] ?? '');
$nic = trim($input['nic'] ?? '');
$creditLimit = floatval($input['credit_limit'] ?? 0);
$creditPeriod = trim($input['credit_period'] ?? '30 Days');
$openingBalance = floatval($input['opening_balance'] ?? 0);
$discountBiscuits = floatval($input['discount_biscuits'] ?? 0);
$discountOther = floatval($input['discount_other'] ?? 0);
$notes = trim($input['notes'] ?? 'Registered via Sales Mobile App');

if (empty($name)) {
    send_json_error('Customer Name is required.');
}

if (empty($phone)) {
    send_json_error('Customer Mobile / Phone Number is required.');
}

try {
    // Check if phone already exists
    $stmtCheck = $db->prepare("SELECT id, name FROM customers WHERE phone = ? AND phone != 'N/A' AND phone != ''");
    $stmtCheck->execute([$phone]);
    if ($existing = $stmtCheck->fetch()) {
        send_json_error('A customer with mobile number "' . htmlspecialchars($phone) . '" already exists (' . htmlspecialchars($existing['name']) . ').');
    }

    $stmt = $db->prepare("
        INSERT INTO customers 
        (title, profile_picture, name, phone, nic, email, address, credit_limit, credit_period, opening_balance, branch, vat_enabled, vat_number, special_discount, discount_biscuits, discount_other, notes) 
        VALUES (?, 'default_avatar.png', ?, ?, ?, ?, ?, ?, ?, ?, 'Main Branch', 0, '', ?, ?, ?, ?)
    ");
    $stmt->execute([
        $title,
        $name,
        $phone,
        $nic,
        $email,
        $address,
        $creditLimit,
        $creditPeriod,
        $openingBalance,
        $discountBiscuits,
        $discountBiscuits,
        $discountOther,
        $notes
    ]);

    $customerId = $db->lastInsertId();
    $fullDisplayName = trim($title . ' ' . $name);

    send_json_response([
        'id' => (int)$customerId,
        'title' => $title,
        'name' => $name,
        'full_display_name' => $fullDisplayName,
        'phone' => $phone,
        'email' => $email,
        'address' => $address,
        'credit_limit' => $creditLimit,
        'opening_balance' => $openingBalance,
        'discount_biscuits' => $discountBiscuits,
        'discount_other' => $discountOther
    ], 'Customer "' . $fullDisplayName . '" registered successfully!');

} catch (Exception $e) {
    send_json_error('Error creating customer: ' . $e->getMessage(), 500);
}
