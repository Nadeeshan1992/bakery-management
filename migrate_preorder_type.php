<?php
// migrate_preorder_type.php - Migration script to update order_type ENUM to 'pos', 'preorder'
$pdo = new PDO('mysql:host=localhost;dbname=bakery_db;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Update existing 'custom_cake' orders to 'preorder'
$pdo->exec("UPDATE orders SET order_type = 'preorder' WHERE order_type = 'custom_cake'");

// Modify ENUM column definition
$pdo->exec("ALTER TABLE orders MODIFY COLUMN order_type ENUM('pos', 'preorder') NOT NULL DEFAULT 'preorder'");

echo "Order type column and existing records successfully updated to 'preorder'.\n";
