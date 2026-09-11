<?php
// modules/orders/add_payment.php - Record Partial / Settlement Payment for Order
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sms_helper.php';

requireLogin();

$orderId = intval($_POST['order_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentAmount = floatval($_POST['payment_amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $chequeRef = trim($_POST['cheque_ref'] ?? $_POST['reference_no'] ?? '');
    $paymentType = $_POST['payment_type'] ?? 'installment';
    $notes = trim($_POST['notes'] ?? 'Order payment settlement');

    if ($orderId > 0 && $paymentAmount > 0) {
        try {
            $db = getDB();
            $db->beginTransaction();

            $stmtO = $db->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
            $stmtO->execute([$orderId]);
            $order = $stmtO->fetch();

            if ($order) {
                $currentPaid = (float)$order['paid_amount'];
                $totalAmount = (float)$order['total_amount'];
                $newPaidAmount = $currentPaid + $paymentAmount;

                $newPaymentStatus = 'partial';
                $changeAmount = 0.00;

                $finalChequeRef = (!empty($chequeRef)) ? $chequeRef : ($order['cheque_ref'] ?? NULL);

                if ($newPaidAmount >= $totalAmount) {
                    $newPaymentStatus = 'paid';
                    $changeAmount = max(0, $newPaidAmount - $totalAmount);

                    // Auto-update order_status to completed when payment is fully settled
                    $stmtUpdate = $db->prepare("
                        UPDATE orders 
                        SET paid_amount = ?, change_amount = ?, payment_status = ?, order_status = 'completed', payment_method = ?, cheque_ref = ? 
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$newPaidAmount, $changeAmount, $newPaymentStatus, $paymentMethod, $finalChequeRef, $orderId]);
                } else {
                    $stmtUpdate = $db->prepare("
                        UPDATE orders 
                        SET paid_amount = ?, change_amount = ?, payment_status = ?, payment_method = ?, cheque_ref = ? 
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$newPaidAmount, $changeAmount, $newPaymentStatus, $paymentMethod, $finalChequeRef, $orderId]);
                }

                // Insert into order_payments audit log
                $stmtPayment = $db->prepare("
                    INSERT INTO order_payments (order_id, payment_amount, payment_method, cheque_ref, payment_type, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtPayment->execute([$orderId, $paymentAmount, $paymentMethod, (!empty($chequeRef) ? $chequeRef : NULL), $paymentType, $notes, getCurrentUserId()]);

                $db->commit();

                // Trigger automated customer SMS receipt
                try {
                    sendPaymentReceiptSMS($orderId, $paymentAmount, $paymentMethod, $db, getCurrentUserId());
                } catch (Exception $e) {
                    error_log("Failed to send payment SMS: " . $e->getMessage());
                }

                $formattedAmount = formatMoney($paymentAmount);
                setFlash('success', "Payment of " . $formattedAmount . " recorded successfully for Order #" . htmlspecialchars($order['order_number']) . "!");
            } else {
                $db->rollBack();
                setFlash('error', 'Order not found.');
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setFlash('error', 'Payment recording failed: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Please enter a valid payment amount.');
    }
}

header('Location: ' . BASE_URL . 'modules/orders/view.php?id=' . $orderId);
exit;
