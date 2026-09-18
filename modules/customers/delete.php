<?php
// modules/customers/delete.php - Delete Customer Account
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlash('error', 'Invalid customer ID.');
    header('Location: ' . BASE_URL . 'modules/customers/index.php');
    exit;
}

if ($id === 1) {
    setFlash('error', 'The Walk-in Customer (#1) is a system default account and cannot be deleted.');
    header('Location: ' . BASE_URL . 'modules/customers/index.php');
    exit;
}

try {
    $db = getDB();

    $stmtCheck = $db->prepare("SELECT id, name FROM customers WHERE id = ?");
    $stmtCheck->execute([$id]);
    $cust = $stmtCheck->fetch();

    if (!$cust) {
        setFlash('error', 'Customer not found.');
        header('Location: ' . BASE_URL . 'modules/customers/index.php');
        exit;
    }

    $stmt = $db->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);

    setFlash('success', 'Customer "' . htmlspecialchars($cust['name']) . '" deleted successfully.');
} catch (Exception $e) {
    setFlash('error', 'Unable to delete customer: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . 'modules/customers/index.php');
exit;
