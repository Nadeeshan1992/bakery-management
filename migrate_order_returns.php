<?php
// migrate_order_returns.php - Migration script to create order_returns table
$pdo = new PDO('mysql:host=localhost;dbname=bakery_db;charset=utf8mb4', 'root', '');

$pdo->exec("
    CREATE TABLE IF NOT EXISTS `order_returns` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT DEFAULT NULL,
        `customer_id` INT DEFAULT NULL,
        `product_id` INT NOT NULL,
        `quantity` DECIMAL(10,2) NOT NULL,
        `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `total_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `reason` ENUM('expired', 'over_order') NOT NULL DEFAULT 'over_order',
        `notes` TEXT DEFAULT NULL,
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

echo "Order returns database migration completed successfully.\n";
