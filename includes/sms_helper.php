<?php
// includes/sms_helper.php - SMS Gateway Engine & Notification Helpers
require_once __DIR__ . '/../config/database.php';

/**
 * Retrieve current active SMS settings from the database
 */
function getSmsSettings($db = null) {
    if (!$db) $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM sms_settings ORDER BY id ASC LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$settings) {
        return [
            'gateway_provider' => 'mock_local',
            'api_url' => 'https://app.notify.lk/api/v1/send',
            'api_key' => '',
            'user_id' => '',
            'sender_id' => 'MLB-BAKERY',
            'is_enabled' => 1,
            'send_on_preorder' => 1,
            'send_on_payment' => 1,
            'preorder_template' => "Dear {customer_name}, your Pre-Order #{order_number} at MLB Bakery is confirmed! Total: Rs. {total_amount}, Paid: Rs. {paid_amount}, Due: Rs. {balance_due}. Delivery: {delivery_date}. Thank you!",
            'payment_template' => "Dear {customer_name}, payment of Rs. {paid_amount} received for Order #{order_number} via {payment_method}. Remaining Balance: Rs. {balance_due}. Thank you for choosing MLB Bakery!"
        ];
    }
    return $settings;
}

/**
 * Format phone number to standard international format (Sri Lanka 94xxxxxxxxx)
 */
function formatSmsPhoneNumber($phone) {
    // Strip all non-numeric characters
    $clean = preg_replace('/[^0-9]/', '', (string)$phone);
    if (empty($clean)) return '';

    // If starts with 0 (e.g. 0771234567 -> 94771234567)
    if (strpos($clean, '0') === 0 && strlen($clean) === 10) {
        return '94' . substr($clean, 1);
    }
    // If 9 digits (e.g. 771234567 -> 94771234567)
    if (strlen($clean) === 9) {
        return '94' . $clean;
    }
    // If already starts with 94
    if (strpos($clean, '94') === 0 && strlen($clean) >= 11) {
        return $clean;
    }

    return $clean;
}

/**
 * Send an SMS message via configured Gateway & log results
 *
 * @param string $phone Customer mobile number
 * @param string $message SMS message body
 * @param string $eventType Event label (e.g. preorder_created, payment_received, test_sms)
 * @param int|null $orderId Associated Order ID
 * @param int|null $customerId Associated Customer ID
 * @param int|null $userId User ID triggering the SMS
 * @return array ['success' => bool, 'status' => string, 'message' => string]
 */
function sendSMS($phone, $message, $eventType = 'general', $orderId = null, $customerId = null, $userId = null) {
    $db = Database::getInstance();
    $settings = getSmsSettings($db);

    $formattedPhone = formatSmsPhoneNumber($phone);
    if (empty($formattedPhone) || strlen($formattedPhone) < 9) {
        logSmsEntry($db, $orderId, $customerId, $phone, $message, $eventType, 'failed', 'Invalid or missing phone number.', $userId);
        return ['success' => false, 'status' => 'failed', 'message' => 'Invalid phone number.'];
    }

    // Check if SMS sending is globally enabled
    if (empty($settings['is_enabled'])) {
        logSmsEntry($db, $orderId, $customerId, $formattedPhone, $message, $eventType, 'disabled', 'SMS sending is disabled in Settings.', $userId);
        return ['success' => false, 'status' => 'disabled', 'message' => 'SMS Gateway is disabled in settings.'];
    }

    $provider = $settings['gateway_provider'] ?? 'mock_local';

    // 1. MOCK LOCAL MODE (Zero-cost local testing)
    if ($provider === 'mock_local') {
        $mockResp = json_encode([
            'status' => 'success',
            'mock' => true,
            'info' => 'Mock SMS delivered successfully in test mode',
            'provider' => 'mock_local',
            'recipient' => $formattedPhone
        ]);
        logSmsEntry($db, $orderId, $customerId, $formattedPhone, $message, $eventType, 'mock_sent', $mockResp, $userId);
        return ['success' => true, 'status' => 'mock_sent', 'message' => 'Mock SMS sent successfully (Test Mode).'];
    }

    // 2. NOTIFY.LK GATEWAY
    if ($provider === 'notify_lk') {
        $apiUrl = !empty($settings['api_url']) ? $settings['api_url'] : 'https://app.notify.lk/api/v1/send';
        $postData = [
            'user_id' => $settings['user_id'] ?? '',
            'api_key' => $settings['api_key'] ?? '',
            'sender_id' => $settings['sender_id'] ?? 'MLB-BAKERY',
            'to' => $formattedPhone,
            'message' => $message
        ];

        try {
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                logSmsEntry($db, $orderId, $customerId, $formattedPhone, $message, $eventType, 'failed', 'CURL Error: ' . $curlErr, $userId);
                return ['success' => false, 'status' => 'failed', 'message' => 'CURL Connection Error: ' . $curlErr];
            }

            $respData = json_decode($response, true);
            $isSuccess = ($httpCode >= 200 && $httpCode < 300) && ($respData['status'] ?? '') === 'success';
            $status = $isSuccess ? 'sent' : 'failed';

            logSmsEntry($db, $orderId, $customerId, $formattedPhone, $message, $eventType, $status, $response, $userId);
            return ['success' => $isSuccess, 'status' => $status, 'message' => $isSuccess ? 'SMS sent successfully.' : 'SMS gateway returned error: ' . $response];

        } catch (Exception $e) {
            logSmsEntry($db, $orderId, $customerId, $formattedPhone, $message, $eventType, 'failed', $e->getMessage(), $userId);
            return ['success' => false, 'status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    // 3. GENERIC REST HTTP GATEWAY
    if ($provider === 'generic_http') {
        $apiUrl = $settings['api_url'];
        $headers = ['Content-Type: application/json'];
        if (!empty($settings['api_key'])) {
            $headers[] = 'Authorization: Bearer ' . $settings['api_key'];
        }

        $payload = [
            'to' => $formattedPhone,
            'message' => $message,
            'sender_id' => $settings['sender_id'] ?? 'MLB-BAKERY'
        ];

        try {
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                logSmsEntry($db, $orderId, $customerId, $formattedPhone, $message, $eventType, 'failed', 'CURL Error: ' . $curlErr, $userId);
                return ['success' => false, 'status' => 'failed', 'message' => 'CURL Error: ' . $curlErr];
            }

            $isSuccess = ($httpCode >= 200 && $httpCode < 300);
            $status = $isSuccess ? 'sent' : 'failed';

            logSmsEntry($db, $orderId, $customerId, $formattedPhone, $message, $eventType, $status, $response, $userId);
            return ['success' => $isSuccess, 'status' => $status, 'message' => $isSuccess ? 'SMS sent.' : 'Gateway HTTP ' . $httpCode . ': ' . $response];

        } catch (Exception $e) {
            logSmsEntry($db, $orderId, $customerId, $formattedPhone, $message, $eventType, 'failed', $e->getMessage(), $userId);
            return ['success' => false, 'status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    return ['success' => false, 'status' => 'failed', 'message' => 'Unknown SMS provider: ' . $provider];
}

/**
 * Internal helper to record SMS transaction log
 */
function logSmsEntry($db, $orderId, $customerId, $phone, $message, $eventType, $status, $responsePayload, $userId = null) {
    try {
        $stmt = $db->prepare("
            INSERT INTO sms_logs (order_id, customer_id, phone_number, message, event_type, status, response_payload, sent_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderId ? intval($orderId) : null,
            $customerId ? intval($customerId) : null,
            $phone,
            $message,
            $eventType,
            $status,
            $responsePayload,
            $userId ? intval($userId) : null
        ]);
    } catch (Exception $e) {
        error_log("Failed to log SMS entry: " . $e->getMessage());
    }
}

/**
 * Trigger: Send Pre-Order Confirmation SMS
 */
function sendOrderConfirmationSMS($orderId, $db = null) {
    if (!$db) $db = Database::getInstance();
    $settings = getSmsSettings($db);

    if (empty($settings['is_enabled']) || empty($settings['send_on_preorder'])) {
        return false;
    }

    $stmt = $db->prepare("
        SELECT o.*, c.name as customer_name, c.title as customer_title, c.phone as customer_phone 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || empty($order['customer_phone']) || $order['customer_phone'] === 'N/A') {
        return false;
    }

    $custName = trim(($order['customer_title'] ?? '') . ' ' . ($order['customer_name'] ?? 'Customer'));
    $totalAmt = number_format((float)$order['total_amount'], 2);
    $paidAmt = number_format((float)$order['paid_amount'], 2);
    $balDue = number_format(max(0, (float)$order['total_amount'] - (float)$order['paid_amount']), 2);
    $delivDate = !empty($order['delivery_date']) ? date('d-M-Y', strtotime($order['delivery_date'])) : 'As scheduled';

    $template = $settings['preorder_template'];
    $placeholders = [
        '{customer_name}' => $custName,
        '{order_number}' => $order['order_number'],
        '{total_amount}' => $totalAmt,
        '{paid_amount}' => $paidAmt,
        '{balance_due}' => $balDue,
        '{delivery_date}' => $delivDate
    ];

    $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);

    return sendSMS(
        $order['customer_phone'],
        $message,
        'preorder_created',
        $orderId,
        $order['customer_id'],
        $order['created_by'] ?? null
    );
}

/**
 * Trigger: Send Payment Receipt / Settlement SMS
 */
function sendPaymentReceiptSMS($orderId, $paymentAmount, $paymentMethod, $db = null, $userId = null) {
    if (!$db) $db = Database::getInstance();
    $settings = getSmsSettings($db);

    if (empty($settings['is_enabled']) || empty($settings['send_on_payment'])) {
        return false;
    }

    $stmt = $db->prepare("
        SELECT o.*, c.name as customer_name, c.title as customer_title, c.phone as customer_phone 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || empty($order['customer_phone']) || $order['customer_phone'] === 'N/A') {
        return false;
    }

    $custName = trim(($order['customer_title'] ?? '') . ' ' . ($order['customer_name'] ?? 'Customer'));
    $paidFormatted = number_format((float)$paymentAmount, 2);
    $newBalDue = number_format(max(0, (float)$order['total_amount'] - (float)$order['paid_amount']), 2);
    $methodFormatted = ucfirst($paymentMethod);

    $template = $settings['payment_template'];
    $placeholders = [
        '{customer_name}' => $custName,
        '{order_number}' => $order['order_number'],
        '{paid_amount}' => $paidFormatted,
        '{payment_method}' => $methodFormatted,
        '{balance_due}' => $newBalDue
    ];

    $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);

    return sendSMS(
        $order['customer_phone'],
        $message,
        'payment_received',
        $orderId,
        $order['customer_id'],
        $userId
    );
}
