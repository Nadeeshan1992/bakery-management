<?php
// migrate_staff_payroll.php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();

    // 1. Staff Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `staff` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `emp_number` VARCHAR(50) NOT NULL UNIQUE,
          `name` VARCHAR(100) NOT NULL,
          `designation` VARCHAR(100) NOT NULL DEFAULT 'Staff',
          `phone` VARCHAR(30) DEFAULT NULL,
          `nic` VARCHAR(30) DEFAULT NULL,
          `address` TEXT DEFAULT NULL,
          `basic_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `daily_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `ot_rate_per_hour` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `epf_no` VARCHAR(50) DEFAULT NULL,
          `bank_name` VARCHAR(100) DEFAULT NULL,
          `account_no` VARCHAR(50) DEFAULT NULL,
          `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table 'staff' created/verified.\n";

    // 2. Staff Attendance Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `staff_attendance` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `staff_id` INT NOT NULL,
          `attendance_date` DATE NOT NULL,
          `status` ENUM('present', 'absent', 'half_day', 'leave') NOT NULL DEFAULT 'present',
          `ot_hours` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
          `notes` VARCHAR(255) DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY `staff_date_unique` (`staff_id`, `attendance_date`),
          FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table 'staff_attendance' created/verified.\n";

    // 3. Staff Salaries Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `staff_salaries` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `staff_id` INT NOT NULL,
          `payroll_month` VARCHAR(7) NOT NULL, -- Format: YYYY-MM
          `total_working_days` INT NOT NULL DEFAULT 30,
          `present_days` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
          `basic_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `earned_basic` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `total_ot_hours` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
          `ot_pay` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `allowances` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `deductions` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `epf_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `net_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `payment_status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
          `paid_date` DATETIME DEFAULT NULL,
          `created_by` INT NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY `staff_month_unique` (`staff_id`, `payroll_month`),
          FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table 'staff_salaries' created/verified.\n";

    // 4. Seed sample staff data if table is empty
    $stmtCount = $db->query("SELECT COUNT(*) FROM staff");
    if ($stmtCount->fetchColumn() == 0) {
        $db->exec("
            INSERT INTO `staff` (`emp_number`, `name`, `designation`, `phone`, `nic`, `basic_salary`, `daily_rate`, `ot_rate_per_hour`, `epf_no`, `status`) VALUES
            ('EMP-001', 'Kamal Perera', 'Head Baker', '0771234567', '901234567V', 65000.00, 2500.00, 350.00, 'EPF-8841', 'active'),
            ('EMP-002', 'Nimali Silva', 'Sales Assistant', '0719876543', '955432100V', 45000.00, 1800.00, 250.00, 'EPF-9932', 'active'),
            ('EMP-003', 'Sunil Fernando', 'Helper / Driver', '0754433221', '883322110V', 40000.00, 1600.00, 220.00, 'EPF-7710', 'active')
        ");
        echo "Sample staff members seeded successfully.\n";
    }

    echo "Staff & Payroll migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
