<?php
// migrate_order_payments.php - Migration script to create order_payments table and backfill initial payments
$pdo = new PDO('mysql:host=localhost;dbname=bakery_db;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("
    CREATE TABLE IF NOT EXISTS `order_payments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `payment_amount` DECIMAL(10,2) NOT NULL,
        `payment_method` ENUM('cash', 'card', 'upi', 'online') NOT NULL DEFAULT 'cash',
        `payment_type` ENUM('advance', 'installment', 'settlement') NOT NULL DEFAULT 'settlement',
        `notes` VARCHAR(255) DEFAULT NULL,
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Backfill initial booking payments for existing orders if not already in order_payments
$pdo->exec("
    INSERT INTO order_payments (order_id, payment_amount, payment_method, payment_type, notes, created_by, created_at)
    SELECT id, paid_amount, payment_method, 
           IF(payment_status='paid' AND paid_amount>=total_amount, 'settlement', 'advance'), 
           'Initial Booking Payment', created_by, created_at 
    FROM orders 
    WHERE paid_amount > 0 
    AND id NOT IN (SELECT DISTINCT order_id FROM order_payments);
");

echo "Order payments database migration and initial payment backfill completed successfully.\n";
