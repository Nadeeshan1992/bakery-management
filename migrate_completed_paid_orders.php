<?php
// migrate_completed_paid_orders.php - Automatically update order_status to 'completed' for fully paid orders
$pdo = new PDO('mysql:host=localhost;dbname=bakery_db;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->exec("
    UPDATE orders 
    SET order_status = 'completed' 
    WHERE (payment_status = 'paid' OR paid_amount >= total_amount) 
    AND order_status != 'cancelled';
");

echo "Successfully updated fully paid orders to 'completed' status. ($stmt rows updated)\n";
