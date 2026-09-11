<?php
$pdo = new PDO('mysql:host=localhost;dbname=bakery_db;charset=utf8mb4', 'root', '');
try {
    $pdo->exec("ALTER TABLE customers ADD COLUMN discount_biscuits DECIMAL(5,2) DEFAULT 0.00 AFTER special_discount");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE customers ADD COLUMN discount_other DECIMAL(5,2) DEFAULT 0.00 AFTER discount_biscuits");
} catch (Exception $e) {}

// Copy existing special_discount to both if needed
$pdo->exec("UPDATE customers SET discount_biscuits = special_discount WHERE discount_biscuits = 0 AND special_discount > 0");
$pdo->exec("UPDATE customers SET discount_other = special_discount WHERE discount_other = 0 AND special_discount > 0");

echo "Customer discount migration completed successfully.\n";
