<?php
// migrate_separate_owner.php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    // Modify ENUM column in users table to make owner and admin separate options
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'admin', 'sales_person', 'pos_operator') NOT NULL DEFAULT 'pos_operator'";
    $db->exec($sql);

    $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
    
    // 1. Owner Account (username: owner)
    $db->exec("INSERT INTO users (username, password, full_name, email, role, status) 
               VALUES ('owner', '$passwordHash', 'Business Owner', 'owner@bakery.com', 'owner', 'active') 
               ON DUPLICATE KEY UPDATE role = 'owner', full_name = 'Business Owner'");

    // 2. Admin Account (username: admin)
    $db->exec("INSERT INTO users (username, password, full_name, email, role, status) 
               VALUES ('admin', '$passwordHash', 'Manager Admin', 'admin@bakery.com', 'admin', 'active') 
               ON DUPLICATE KEY UPDATE role = 'admin', full_name = 'Manager Admin'");

    echo "Owner and Admin roles separated successfully in database.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
