<?php
// api/helpers.php - API Authentication & Security Helpers
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
if (!ob_get_level()) {
    ob_start();
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Token');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}

require_once __DIR__ . '/../config/database.php';

function send_json_response($data = [], $message = 'Success', $code = 200) {
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function send_json_error($message = 'An error occurred', $code = 400) {
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

function get_json_input() {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return $_POST;
}

function authenticateApiUser() {
    $headers = getallheaders();
    $token = $headers['X-API-Token'] ?? $headers['x-api-token'] ?? $_GET['token'] ?? $_POST['token'] ?? '';

    if (empty($token) && isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        }
    }

    if (empty($token)) {
        send_json_error('Unauthorized: Missing API authentication token.', 401);
    }

    try {
        $json = base64_decode($token);
        $data = json_decode($json, true);

        if (!$data || empty($data['user_id'])) {
            throw new Exception('Invalid token payload.');
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, full_name, role, status FROM users WHERE id = ? AND status = 'active'");
        $stmt->execute([$data['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('User account disabled or not found.');
        }

        return $user;

    } catch (Exception $e) {
        send_json_error('Unauthorized: ' . $e->getMessage(), 401);
    }
}
