<?php
// modules/payroll/process.php - Process and Store Payroll Vouchers
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['admin', 'owner']);

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payrollMonth = trim($_POST['payroll_month'] ?? date('Y-m'));
    $presentDaysArr = $_POST['present_days'] ?? [];
    $totalOtHoursArr = $_POST['total_ot_hours'] ?? [];
    $allowancesArr = $_POST['allowances'] ?? [];
    $deductionsArr = $_POST['deductions'] ?? [];
    $epfDeductionsArr = $_POST['epf_deduction'] ?? [];

    $foodDeductionsArr = $_POST['food_deduction'] ?? [];
    $advanceDeductionsArr = $_POST['advance_deduction'] ?? [];

    $userId = getCurrentUserId();
    $processedCount = 0;

    try {
        $db->beginTransaction();

        $stmtStaff = $db->query("SELECT * FROM staff WHERE status = 'active'");
        $staffMembers = $stmtStaff->fetchAll();

        $stmtUpsert = $db->prepare("
            INSERT INTO staff_salaries (
                staff_id, payroll_month, total_working_days, present_days, basic_salary, earned_basic,
                total_ot_hours, ot_pay, allowances, deductions, advance_deduction, food_deduction, epf_deduction, net_salary, payment_status, paid_date, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', NOW(), ?)
            ON DUPLICATE KEY UPDATE 
                present_days = VALUES(present_days),
                earned_basic = VALUES(earned_basic),
                total_ot_hours = VALUES(total_ot_hours),
                ot_pay = VALUES(ot_pay),
                allowances = VALUES(allowances),
                deductions = VALUES(deductions),
                advance_deduction = VALUES(advance_deduction),
                food_deduction = VALUES(food_deduction),
                epf_deduction = VALUES(epf_deduction),
                net_salary = VALUES(net_salary),
                payment_status = 'paid',
                paid_date = NOW(),
                created_by = VALUES(created_by)
        ");

        foreach ($staffMembers as $staff) {
            $staffId = $staff['id'];
            $presentDays = (float)($presentDaysArr[$staffId] ?? 30);
            if ($presentDays <= 0) {
                $presentDays = 30.0;
            }
            $totalOtHours = (float)($totalOtHoursArr[$staffId] ?? 0);
            $allowance = (float)($allowancesArr[$staffId] ?? 0);
            $deduction = (float)($deductionsArr[$staffId] ?? 0);
            $foodDeduction = (float)($foodDeductionsArr[$staffId] ?? 0);
            $advanceDeduction = (float)($advanceDeductionsArr[$staffId] ?? 0);

            // Fetch live advance total for Month to guarantee accuracy
            $stmtLiveAdv = $db->prepare("SELECT COALESCE(SUM(advance_amount), 0) FROM staff_advances WHERE staff_id = ? AND DATE_FORMAT(advance_date, '%Y-%m') = ?");
            $stmtLiveAdv->execute([$staffId, $payrollMonth]);
            $liveAdv = (float)$stmtLiveAdv->fetchColumn();
            if ($liveAdv > 0) {
                $advanceDeduction = $liveAdv;
            }

            // Fetch live food total for Month to guarantee accuracy
            $stmtLiveFood = $db->prepare("SELECT COALESCE(SUM(total_price), 0) FROM staff_food_consumption WHERE staff_id = ? AND DATE_FORMAT(consumption_date, '%Y-%m') = ?");
            $stmtLiveFood->execute([$staffId, $payrollMonth]);
            $liveFood = (float)$stmtLiveFood->fetchColumn();
            if ($liveFood > 0) {
                $foodDeduction = $liveFood;
            }

            $basicSalary = (float)$staff['basic_salary'];
            $dailyRate = (float)$staff['daily_rate'];
            $otRate = (float)$staff['ot_rate_per_hour'];

            if ($dailyRate > 0) {
                $earnedBasic = $dailyRate * $presentDays;
            } else {
                $earnedBasic = ($basicSalary / 30) * $presentDays;
            }

            $otPay = $totalOtHours * $otRate;
            $epfDeduction = (float)($epfDeductionsArr[$staffId] ?? ($earnedBasic * 0.08));
            $netSalary = max(0, $earnedBasic + $otPay + $allowance - $deduction - $advanceDeduction - $foodDeduction - $epfDeduction);

            $stmtUpsert->execute([
                $staffId,
                $payrollMonth,
                30,
                $presentDays,
                $basicSalary,
                $earnedBasic,
                $totalOtHours,
                $otPay,
                $allowance,
                $deduction,
                $advanceDeduction,
                $foodDeduction,
                $epfDeduction,
                $netSalary,
                $userId
            ]);
            $processedCount++;
        }

        $db->commit();
        setFlash('success', "Payroll for $payrollMonth processed and marked as paid for $processedCount staff member(s)!");
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', "Failed to process payroll: " . $e->getMessage());
    }

    header('Location: ' . BASE_URL . 'modules/payroll/index.php?month=' . urlencode($payrollMonth));
    exit;
}
