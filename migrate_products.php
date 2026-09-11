<?php
$pdo = new PDO('mysql:host=localhost;dbname=bakery_db;charset=utf8mb4', 'root', '');
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN price_retail DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER name");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN price_wholesale DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price_retail");
} catch (Exception $e) {}

// Copy existing price to price_retail and price_wholesale if 0
$pdo->exec("UPDATE products SET price_retail = price WHERE price_retail = 0");
$pdo->exec("UPDATE products SET price_wholesale = price * 0.90 WHERE price_wholesale = 0");

echo "Products migration done.\n";
