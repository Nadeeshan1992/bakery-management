<?php
// index.php - Main entry point
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
} else {
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
}
exit;
