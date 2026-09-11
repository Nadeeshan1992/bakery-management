<?php
// migrate_payment_methods.php - Update ENUM payment_method columns in MySQL to cash, cheque, online
$pdo = new PDO('mysql:host=localhost;dbname=bakery_db;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Convert old values like 'card' or 'upi' to 'online' before altering ENUM column
$pdo->exec("UPDATE orders SET payment_method = 'online' WHERE payment_method IN ('card', 'upi')");
$pdo->exec("UPDATE order_payments SET payment_method = 'online' WHERE payment_method IN ('card', 'upi')");

// Alter ENUM definition for orders table
$pdo->exec("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash', 'cheque', 'online') NOT NULL DEFAULT 'cash'");

// Alter ENUM definition for order_payments table
$pdo->exec("ALTER TABLE order_payments MODIFY COLUMN payment_method ENUM('cash', 'cheque', 'online') NOT NULL DEFAULT 'cash'");

echo "Payment methods database columns successfully updated to ('cash', 'cheque', 'online').\n";
