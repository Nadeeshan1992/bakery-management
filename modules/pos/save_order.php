<?php
// modules/pos/save_order.php - Process POS Checkout
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireRole(['admin', 'owner', 'pos_operator']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartJson = $_POST['cart_data'] ?? '';
    $customerId = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : 1;
    $discount = floatval($_POST['discount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $chequeRef = trim($_POST['cheque_ref'] ?? '');
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);

    $cart = json_decode($cartJson, true);

    if (empty($cart) || !is_array($cart)) {
        setFlash('error', 'Cannot complete checkout with an empty cart.');
        header('Location: ' . BASE_URL . 'modules/pos/index.php');
        exit;
    }

    try {
        $db = getDB();

        // Server-Side Out of Stock Check
        foreach ($cart as $item) {
            $pId = intval($item['id']);
            $reqQty = intval($item['qty']);
            $stmtStockCheck = $db->prepare("SELECT name, current_stock FROM products WHERE id = ?");
            $stmtStockCheck->execute([$pId]);
            $pData = $stmtStockCheck->fetch();

            if (!$pData || floatval($pData['current_stock']) < $reqQty) {
                $avail = $pData ? intval($pData['current_stock']) : 0;
                setFlash('error', 'Cannot complete sale: "' . htmlspecialchars($item['name']) . '" is OUT OF STOCK! (Available: ' . $avail . ' pcs, Requested: ' . $reqQty . ' pcs). Please enter daily item stock first.');
                header('Location: ' . BASE_URL . 'modules/pos/index.php');
                exit;
            }
        }

        $db->beginTransaction();

        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += floatval($item['price']) * intval($item['qty']);
        }

        $totalAmount = max(0, $subtotal - $discount);
        $tenderedPaidAmount = floatval($_POST['paid_amount'] ?? 0);
        if ($tenderedPaidAmount < $totalAmount) {
            $tenderedPaidAmount = $totalAmount; // Default paid to total if zero or less
        }
        $changeAmount = max(0, $tenderedPaidAmount - $totalAmount);
        $actualPaidAmount = min($tenderedPaidAmount, $totalAmount);

        // Generate unique order number (e.g. ORD-20260902-1234)
        $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

        // Insert Order
        $stmtOrder = $db->prepare("
            INSERT INTO orders (order_number, customer_id, order_type, subtotal, discount, tax, total_amount, paid_amount, change_amount, payment_method, cheque_ref, payment_status, order_status, created_by) 
            VALUES (?, ?, 'pos', ?, ?, 0.00, ?, ?, ?, ?, ?, 'paid', 'completed', ?)
        ");
        $stmtOrder->execute([
            $orderNumber,
            $customerId,
            $subtotal,
            $discount,
            $totalAmount,
            $actualPaidAmount,
            $changeAmount,
            $paymentMethod,
            (!empty($chequeRef) ? $chequeRef : NULL),
            getCurrentUserId()
        ]);

        $orderId = $db->lastInsertId();

        // Insert initial POS payment into order_payments audit log
        $stmtPayInit = $db->prepare("
            INSERT INTO order_payments (order_id, payment_amount, payment_method, cheque_ref, payment_type, notes, created_by) 
            VALUES (?, ?, ?, ?, 'settlement', 'POS Counter checkout settlement', ?)
        ");
        $stmtPayInit->execute([
            $orderId,
            $totalAmount,
            $paymentMethod,
            (!empty($chequeRef) ? $chequeRef : NULL),
            getCurrentUserId()
        ]);

        // Insert Order Items & Deduct Finished Product Stock
        $stmtItem = $db->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmtDeductStock = $db->prepare("UPDATE products SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?");
        $stmtStockLog = $db->prepare("
            INSERT INTO product_stock_logs (product_id, log_type, quantity, reference_order_id, notes, created_by) 
            VALUES (?, 'sale_deduction', ?, ?, ?, ?)
        ");

        foreach ($cart as $item) {
            $prodId = intval($item['id']);
            $qty = intval($item['qty']);
            $itemSubtotal = floatval($item['price']) * $qty;

            $stmtItem->execute([
                $orderId,
                $prodId,
                $item['name'],
                $qty,
                floatval($item['price']),
                $itemSubtotal
            ]);

            // Deduct finished product stock & record audit log
            if ($prodId > 0 && $qty > 0) {
                $stmtDeductStock->execute([$qty, $prodId]);
                $stmtStockLog->execute([$prodId, $qty, $orderId, 'POS Counter Sale #' . $orderNumber, getCurrentUserId()]);
            }
        }

        $db->commit();

        setFlash('success', 'Sale completed successfully! Invoice #' . $orderNumber);
        header('Location: ' . BASE_URL . 'modules/pos/invoice.php?id=' . $orderId);
        exit;

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        setFlash('error', 'Checkout failed: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'modules/pos/index.php');
        exit;
    }
}
