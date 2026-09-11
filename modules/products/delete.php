<?php
// modules/products/delete.php - Delete product
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Product deleted successfully.');
    } catch (Exception $e) {
        setFlash('error', 'Unable to delete product: ' . $e->getMessage());
    }
}

header('Location: ' . BASE_URL . 'modules/products/index.php');
exit;
