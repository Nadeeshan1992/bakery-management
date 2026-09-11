<?php
// migrate_sms_settings.php - Database migration for SMS Gateway & Logs
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();

    // 1. Create sms_settings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS sms_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gateway_provider VARCHAR(50) NOT NULL DEFAULT 'mock_local',
            api_url VARCHAR(255) NOT NULL DEFAULT 'https://app.notify.lk/api/v1/send',
            api_key VARCHAR(255) NULL DEFAULT '',
            user_id VARCHAR(100) NULL DEFAULT '',
            sender_id VARCHAR(50) NOT NULL DEFAULT 'MLB-BAKERY',
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            send_on_preorder TINYINT(1) NOT NULL DEFAULT 1,
            send_on_payment TINYINT(1) NOT NULL DEFAULT 1,
            preorder_template TEXT NOT NULL,
            payment_template TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed default settings row if empty
    $count = $db->query("SELECT COUNT(*) FROM sms_settings")->fetchColumn();
    if ($count == 0) {
        $defaultPreorderTpl = "Dear {customer_name}, your Pre-Order #{order_number} at MLB Bakery is confirmed! Total: Rs. {total_amount}, Paid: Rs. {paid_amount}, Due: Rs. {balance_due}. Delivery: {delivery_date}. Thank you!";
        $defaultPaymentTpl = "Dear {customer_name}, payment of Rs. {paid_amount} received for Order #{order_number} via {payment_method}. Remaining Balance: Rs. {balance_due}. Thank you for choosing MLB Bakery!";

        $stmt = $db->prepare("
            INSERT INTO sms_settings (gateway_provider, api_url, api_key, user_id, sender_id, is_enabled, send_on_preorder, send_on_payment, preorder_template, payment_template)
            VALUES ('mock_local', 'https://app.notify.lk/api/v1/send', '', '', 'MLB-BAKERY', 1, 1, 1, ?, ?)
        ");
        $stmt->execute([$defaultPreorderTpl, $defaultPaymentTpl]);
        echo "Default SMS Settings seeded.\n";
    }

    // 2. Create sms_logs table
    $db->exec("
        CREATE TABLE IF NOT EXISTS sms_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NULL,
            customer_id INT NULL,
            phone_number VARCHAR(30) NOT NULL,
            message TEXT NOT NULL,
            event_type VARCHAR(50) NOT NULL DEFAULT 'general',
            status VARCHAR(30) NOT NULL DEFAULT 'sent',
            response_payload TEXT NULL,
            sent_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_order (order_id),
            INDEX idx_cust (customer_id),
            INDEX idx_phone (phone_number),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "SMS Settings and Logs tables successfully created / verified.\n";

} catch (Exception $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
