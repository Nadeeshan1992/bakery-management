<?php
$pdo = new PDO('mysql:host=localhost;dbname=bakery_db;charset=utf8mb4', 'root', '');
$cols = [
    "title VARCHAR(20) DEFAULT 'Mr.'",
    "profile_picture VARCHAR(255) DEFAULT 'default_avatar.png'",
    "nic VARCHAR(30) DEFAULT NULL",
    "credit_limit DECIMAL(10,2) DEFAULT 0.00",
    "credit_period VARCHAR(50) DEFAULT '30 Days'",
    "opening_balance DECIMAL(10,2) DEFAULT 0.00",
    "branch VARCHAR(100) DEFAULT 'Main Branch'",
    "vat_enabled TINYINT(1) DEFAULT 0",
    "vat_number VARCHAR(50) DEFAULT NULL",
    "special_discount DECIMAL(5,2) DEFAULT 0.00"
];

foreach ($cols as $col) {
    try {
        $pdo->exec("ALTER TABLE customers ADD COLUMN $col");
    } catch (Exception $e) {
        // column might already exist
    }
}
echo "Migration done.\n";
