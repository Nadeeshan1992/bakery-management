<?php
// migrate_finished_stock.php - Migration script to add finished product stock tracking columns & logs table
$pdo = new PDO('mysql:host=localhost;dbname=bakery_db;charset=utf8mb4', 'root', '');

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN current_stock DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN min_stock_alert DECIMAL(10,2) NOT NULL DEFAULT 10.00 AFTER current_stock");
} catch (Exception $e) {}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS `product_stock_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `log_type` ENUM('daily_batch', 'sale_deduction', 'adjustment', 'waste') NOT NULL DEFAULT 'daily_batch',
        `quantity` DECIMAL(10,2) NOT NULL,
        `reference_order_id` INT DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Initialize initial stock values for existing products to 50 if zero
$pdo->exec("UPDATE products SET current_stock = 50.00 WHERE current_stock = 0.00");

echo "Finished goods stock tracking migration completed successfully.\n";
