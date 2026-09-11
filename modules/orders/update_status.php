<?php
// modules/orders/update_status.php - Update Order & Payment Status
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $orderStatus = $_POST['order_status'] ?? 'pending';
    $paymentStatus = $_POST['payment_status'] ?? 'unpaid';

    if ($orderId > 0) {
        try {
            $db = getDB();
            if ($paymentStatus === 'paid') {
                $orderStatus = 'completed';
            }
            $stmt = $db->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
            $stmt->execute([$orderStatus, $paymentStatus, $orderId]);
            setFlash('success', 'Order status updated to ' . strtoupper($orderStatus));
        } catch (Exception $e) {
            setFlash('error', 'Status update failed: ' . $e->getMessage());
        }
    }
}

header('Location: ' . BASE_URL . 'modules/orders/view.php?id=' . $orderId);
exit;
