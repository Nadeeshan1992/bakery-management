<?php
// migrate_staff_allowance.php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();

    $stmtCheck = $db->query("SHOW COLUMNS FROM staff LIKE 'fixed_allowance'");
    if (!$stmtCheck->fetch()) {
        $db->exec("ALTER TABLE staff ADD COLUMN `fixed_allowance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `ot_rate_per_hour`          ");
        echo "Column 'fixed_allowance' added to staff table.\n";
    } else {
        echo "Column 'fixed_allowance' already exists.\n";
    }

    echo "Staff allowance migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
