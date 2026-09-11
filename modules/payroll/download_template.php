<?php
// modules/payroll/download_template.php - Export Attendance CSV Template
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['admin', 'owner']);

$db = getDB();
$stmt = $db->query("SELECT emp_number, name FROM staff WHERE status = 'active' ORDER BY emp_number ASC");
$staffList = $stmt->fetchAll();

$today = date('Y-m-d');

// Set CSV download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="attendance_template_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['emp_number', 'date', 'status', 'ot_hours', 'notes', 'employee_name_reference']);

// Pre-fill active staff rows for today
foreach ($staffList as $staff) {
    fputcsv($output, [
        $staff['emp_number'],
        $today,
        'present', // options: present, absent, half_day, leave
        '0',       // OT hours (e.g. 2.5)
        'Regular Shift',
        $staff['name']
    ]);
}

fclose($output);
exit;
