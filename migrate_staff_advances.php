<?php
// migrate_staff_advances.php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();

    // 1. Create staff_advances table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `staff_advances` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `staff_id` INT NOT NULL,
          `advance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `advance_date` DATE NOT NULL,
          `notes` VARCHAR(255) DEFAULT NULL,
          `created_by` INT NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table 'staff_advances' created/verified.\n";

    // 2. Add advance_deduction column to staff_salaries if not exists
    $stmtCheck = $db->query("SHOW COLUMNS FROM staff_salaries LIKE 'advance_deduction'");
    if (!$stmtCheck->fetch()) {
        $db->exec("ALTER TABLE staff_salaries ADD COLUMN `advance_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `deductions`");
        echo "Column 'advance_deduction' added to staff_salaries.\n";
    }

    echo "Staff salary advances migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
