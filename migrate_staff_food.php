<?php
// migrate_staff_food.php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();

    // 1. Create staff_food_consumption table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `staff_food_consumption` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `staff_id` INT NOT NULL,
          `product_id` INT NOT NULL,
          `product_name` VARCHAR(150) NOT NULL,
          `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
          `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `consumption_date` DATE NOT NULL,
          `notes` VARCHAR(255) DEFAULT NULL,
          `created_by` INT NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table 'staff_food_consumption' created/verified.\n";

    // 2. Add food_deduction column to staff_salaries if not exists
    $stmtCheck = $db->query("SHOW COLUMNS FROM staff_salaries LIKE 'food_deduction'");
    if (!$stmtCheck->fetch()) {
        $db->exec("ALTER TABLE staff_salaries ADD COLUMN `food_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `deductions`");
        echo "Column 'food_deduction' added to staff_salaries.\n";
    }

    // 3. Add bill_no column to staff_food_consumption if not exists
    $stmtBillCheck = $db->query("SHOW COLUMNS FROM staff_food_consumption LIKE 'bill_no'");
    if (!$stmtBillCheck->fetch()) {
        $db->exec("ALTER TABLE staff_food_consumption ADD COLUMN `bill_no` VARCHAR(50) DEFAULT NULL AFTER `id`, ADD INDEX `idx_bill_no` (`bill_no`)");
        $db->exec("UPDATE staff_food_consumption SET `bill_no` = CONCAT('SFC-', LPAD(id, 5, '0')) WHERE `bill_no` IS NULL OR `bill_no` = ''");
        echo "Column 'bill_no' added to staff_food_consumption and existing rows backfilled.\n";
    }

    echo "Staff food consumption migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
