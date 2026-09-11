<?php
// migrate_user_roles.php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    // Modify ENUM column in users table
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'owner', 'sales_person', 'pos_operator') NOT NULL DEFAULT 'pos_operator'";
    $db->exec($sql);
    echo "Users table role ENUM updated successfully.\n";

    // Update existing users if any
    $db->exec("UPDATE users SET role = 'admin' WHERE username = 'admin'");
    
    // Insert/update new demo users (password: password123)
    $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
    
    // 1. Admin / Owner
    $db->exec("INSERT INTO users (id, username, password, full_name, email, role, status) 
               VALUES (1, 'admin', '$passwordHash', 'Owner Admin', 'admin@bakery.com', 'admin', 'active') 
               ON DUPLICATE KEY UPDATE role = 'admin', full_name = 'Owner Admin'");

    // 2. Sales Person
    $db->exec("INSERT INTO users (id, username, password, full_name, email, role, status) 
               VALUES (2, 'sales', '$passwordHash', 'John Sales', 'sales@bakery.com', 'sales_person', 'active') 
               ON DUPLICATE KEY UPDATE role = 'sales_person', full_name = 'John Sales'");

    // 3. POS Operator
    $db->exec("INSERT INTO users (id, username, password, full_name, email, role, status) 
               VALUES (3, 'pos', '$passwordHash', 'Sarah POS Operator', 'pos@bakery.com', 'pos_operator', 'active') 
               ON DUPLICATE KEY UPDATE role = 'pos_operator', full_name = 'Sarah POS Operator'");

    // Remove old baker user if exists
    $db->exec("DELETE FROM users WHERE username = 'baker'");

    echo "Demo users updated: admin (Admin/Owner), sales (Sales Person), pos (POS Operator).\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
