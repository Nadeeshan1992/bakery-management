<?php
// modules/auth/logout.php - Logout handler
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

session_start();
session_unset();
session_destroy();

header('Location: ' . BASE_URL . 'modules/auth/login.php');
exit;
