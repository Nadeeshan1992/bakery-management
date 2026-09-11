<?php
// includes/auth.php - Session Authentication & Security Helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'modules/auth/login.php');
        exit;
    }
}

function requireRole($allowedRoles = []) {
    requireLogin();
    $userRole = $_SESSION['user_role'] ?? '';
    // Admin and Owner have full powers across all modules
    if (in_array($userRole, ['admin', 'owner'])) {
        return;
    }
    if (!in_array($userRole, (array)$allowedRoles)) {
        $_SESSION['flash_error'] = "Access denied: You do not have permission to access that feature.";
        header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
        exit;
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? 0;
}

function getCurrentUserName() {
    return $_SESSION['full_name'] ?? 'User';
}

function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? 'guest';
}

// Generate CSRF Token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
