<?php
// api/auth/login.php - Mobile REST API Authentication Endpoint
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Token');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$username = trim($input['username'] ?? $_POST['username'] ?? '');
$password = trim($input['password'] ?? $_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required.'
    ]);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, full_name, role, password, status FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $tokenData = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'time' => time()
        ];
        $apiToken = base64_encode(json_encode($tokenData));

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Welcome back, ' . $user['full_name'] . '!',
            'data' => [
                'user_id' => (int)$user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'api_token' => $apiToken
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database Error: ' . $e->getMessage()
    ]);
}
