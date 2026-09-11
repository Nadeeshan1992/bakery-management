<?php
// modules/payroll/summary_sheet.php - Master Monthly Salary Summary Sheet (All Staff)
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['admin', 'owner']);

$db = getDB();
$selectedMonth = $_GET['month'] ?? date('Y-m');

// Fetch all active staff
$stmtStaff = $db->query("SELECT * FROM staff WHERE status = 'active' ORDER BY emp_number ASC");
$staffMembers = $stmtStaff->fetchAll();

// Fetch processed salaries for selected month if any
$stmtSalaries = $db->prepare("
    SELECT s.*, u.full_name as processed_by_name 
    FROM staff_salaries s 
    LEFT JOIN users u ON s.created_by = u.id 
    WHERE s.payroll_month = ?
");
$stmtSalaries->execute([$selectedMonth]);
$existingSalaries = [];
$processedBy = null;
$payrollDate = null;
foreach ($stmtSalaries->fetchAll() as $sRow) {
    $existingSalaries[$sRow['staff_id']] = $sRow;
    if (!$processedBy && !empty($sRow['processed_by_name'])) {
        $processedBy = $sRow['processed_by_name'];
    }
    if (!$payrollDate && !empty($sRow['paid_date'])) {
        $payrollDate = $sRow['paid_date'];
    }
}

// Attendance summaries
$stmtAtt = $db->prepare("
    SELECT staff_id, 
           SUM(CASE WHEN status = 'present' THEN 1 WHEN status = 'half_day' THEN 0.5 ELSE 0 END) as present_days,
           SUM(ot_hours) as total_ot_hours
    FROM staff_attendance 
    WHERE DATE_FORMAT(attendance_date, '%Y-%m') = ?
    GROUP BY staff_id
");
$stmtAtt->execute([$selectedMonth]);
$attendanceSummaries = [];
foreach ($stmtAtt->fetchAll() as $aRow) {
    $attendanceSummaries[$aRow['staff_id']] = $aRow;
}

// Food summaries
$stmtFood = $db->prepare("
    SELECT staff_id, COALESCE(SUM(total_price), 0) as food_total 
    FROM staff_food_consumption 
    WHERE DATE_FORMAT(consumption_date, '%Y-%m') = ? 
    GROUP BY staff_id
");
$stmtFood->execute([$selectedMonth]);
$foodSummaries = [];
foreach ($stmtFood->fetchAll() as $fRow) {
    $foodSummaries[$fRow['staff_id']] = (float)$fRow['food_total'];
}

// Advance summaries
$stmtAdv = $db->prepare("
    SELECT staff_id, COALESCE(SUM(advance_amount), 0) as advance_total 
    FROM staff_advances 
    WHERE DATE_FORMAT(advance_date, '%Y-%m') = ? 
    GROUP BY staff_id
");
$stmtAdv->execute([$selectedMonth]);
$advanceSummaries = [];
foreach ($stmtAdv->fetchAll() as $advRow) {
    $advanceSummaries[$advRow['staff_id']] = (float)$advRow['advance_total'];
}

// Compute rows and grand totals
$rows = [];
$totals = [
    'basic' => 0,
    'earned_basic' => 0,
    'ot_hours' => 0,
    'ot_pay' => 0,
    'allowance' => 0,
    'advance' => 0,
    'food' => 0,
    'epf' => 0,
    'other_ded' => 0,
    'gross' => 0,
    'total_ded' => 0,
    'net' => 0
];

foreach ($staffMembers as $staff) {
    $staffId = $staff['id'];
    $att = $attendanceSummaries[$staffId] ?? null;
    $existing = $existingSalaries[$staffId] ?? null;

    if ($existing !== null && isset($existing['present_days'])) {
        $presentDays = (float)$existing['present_days'];
    } elseif ($att !== null && isset($att['present_days'])) {
        $presentDays = (float)$att['present_days'];
    } else {
        $presentDays = 30.0;
    }

    $totalOtHours = (float)($existing['total_ot_hours'] ?? ($att['total_ot_hours'] ?? 0));
    $basicSalary = (float)$staff['basic_salary'];
    $dailyRate = (float)$staff['daily_rate'];
    $otRate = (float)$staff['ot_rate_per_hour'];

    if ($existing !== null && isset($existing['earned_basic'])) {
        $earnedBasic = (float)$existing['earned_basic'];
    } else {
        if ($dailyRate > 0) {
            $earnedBasic = $dailyRate * $presentDays;
        } else {
            $earnedBasic = ($basicSalary / 30) * $presentDays;
        }
    }

    $otPay = $existing !== null ? (float)$existing['ot_pay'] : ($totalOtHours * $otRate);
    $allowance = (float)($existing['allowances'] ?? ($staff['fixed_allowance'] ?? 0));
    $otherDed = (float)($existing['deductions'] ?? 0);

    $liveAdv = (float)($advanceSummaries[$staffId] ?? 0);
    $advanceDed = ($liveAdv > 0) ? $liveAdv : (float)($existing['advance_deduction'] ?? 0);

    $liveFood = (float)($foodSummaries[$staffId] ?? 0);
    $foodDed = ($liveFood > 0) ? $liveFood : (float)($existing['food_deduction'] ?? 0);

    $epfDed = (float)($existing['epf_deduction'] ?? ($earnedBasic * 0.08));

    $gross = $earnedBasic + $otPay + $allowance;
    $totalDed = $advanceDed + $foodDed + $epfDed + $otherDed;
    $net = max(0, $gross - $totalDed);
    $isPaid = ($existing && $existing['payment_status'] === 'paid');

    $row = [
        'emp_number' => $staff['emp_number'],
        'name' => $staff['name'],
        'designation' => $staff['designation'],
        'basic' => $basicSalary,
        'present_days' => $presentDays,
        'earned_basic' => $earnedBasic,
        'ot_hours' => $totalOtHours,
        'ot_pay' => $otPay,
        'allowance' => $allowance,
        'advance' => $advanceDed,
        'food' => $foodDed,
        'epf' => $epfDed,
        'other_ded' => $otherDed,
        'gross' => $gross,
        'total_ded' => $totalDed,
        'net' => $net,
        'is_paid' => $isPaid
    ];

    $rows[] = $row;

    $totals['basic'] += $basicSalary;
    $totals['earned_basic'] += $earnedBasic;
    $totals['ot_hours'] += $totalOtHours;
    $totals['ot_pay'] += $otPay;
    $totals['allowance'] += $allowance;
    $totals['advance'] += $advanceDed;
    $totals['food'] += $foodDed;
    $totals['epf'] += $epfDed;
    $totals['other_ded'] += $otherDed;
    $totals['gross'] += $gross;
    $totals['total_ded'] += $totalDed;
    $totals['net'] += $net;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Payroll Summary Sheet - <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #212529;
            font-size: 13px;
        }
        .summary-container {
            max-width: 1200px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 30px;
            border: 1px solid #e9ecef;
        }
        .header-logo {
            height: 60px;
            border-radius: 6px;
        }
        .table-payroll th {
            background-color: #f1f3f5 !important;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-payroll td {
            vertical-align: middle;
            font-size: 12px;
        }
        .table-payroll .grand-total-row td {
            background-color: #e9ecef !important;
            font-weight: bold;
            font-size: 13px;
        }
        @media print {
            @page {
                size: landscape;
                margin: 8mm;
            }
            body {
                background: #ffffff !important;
                font-size: 10px !important;
            }
            .summary-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
            .no-print {
                display: none !important;
            }
            .table-payroll th {
                font-size: 9px !important;
                padding: 4px 6px !important;
            }
            .table-payroll td {
                font-size: 9px !important;
                padding: 4px 6px !important;
            }
            .table-payroll .grand-total-row td {
                font-size: 10px !important;
            }
        }
    </style>
</head>
<body>

<!-- Control Header (No Print) -->
<div class="container text-end mt-3 mb-2 no-print" style="max-width: 1200px;">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border mb-3">
        <div class="d-flex align-items-center gap-3">
            <a href="<?php echo BASE_URL; ?>modules/payroll/index.php?month=<?php echo htmlspecialchars($selectedMonth); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Payroll Processing
            </a>
            <form method="GET" action="" class="d-flex align-items-center gap-2 mb-0">
                <label for="month" class="form-label mb-0 fw-bold small text-muted"><i class="fa-solid fa-calendar-days text-primary me-1"></i> Month:</label>
                <input type="month" id="month" name="month" class="form-control form-control-sm" value="<?php echo htmlspecialchars($selectedMonth); ?>" onchange="this.form.submit()">
            </form>
        </div>
        <button onclick="window.print()" class="btn btn-warning text-dark font-weight-bold shadow-sm">
            <i class="fa-solid fa-print me-1"></i> Print Master Summary Sheet
        </button>
    </div>
</div>

<div class="summary-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            <img src="<?php echo BASE_URL; ?>assets/images/mlb_logo.jpg" alt="MLB Logo" class="header-logo">
            <div>
                <h4 class="fw-bold mb-0 text-dark">MLB Bakery Management POS</h4>
                <small class="text-muted">No. 124 Bakery Road, Main Street | Tel: 077-1234567 | Reg: BK-8942</small>
            </div>
        </div>
        <div class="text-end">
            <h5 class="fw-bold text-uppercase text-warning mb-1"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Master Monthly Payroll Sheet</h5>
            <div class="badge bg-dark text-white fs-6">Month: <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped table-payroll align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-center" style="width: 40px;">#</th>
                    <th>EMP #</th>
                    <th>Staff Member</th>
                    <th>Designation</th>
                    <th class="text-end">Basic Salary</th>
                    <th class="text-center">Days</th>
                    <th class="text-end">Earned Basic</th>
                    <th class="text-end">OT Pay</th>
                    <th class="text-end">Allowances</th>
                    <th class="text-end text-danger">Advance</th>
                    <th class="text-end text-danger">Food</th>
                    <th class="text-end text-danger">EPF (8%)</th>
                    <th class="text-end text-danger">Other Ded.</th>
                    <th class="text-end text-success fw-bold">Net Salary</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="15" class="text-center text-muted py-4">No active staff members found for this payroll period.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $idx => $r): ?>
                        <tr>
                            <td class="text-center text-muted"><?php echo $idx + 1; ?></td>
                            <td><code><?php echo htmlspecialchars($r['emp_number']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($r['designation']); ?></small></td>
                            <td class="text-end"><?php echo number_format($r['basic'], 2); ?></td>
                            <td class="text-center"><?php echo number_format($r['present_days'], 1); ?></td>
                            <td class="text-end fw-semibold"><?php echo number_format($r['earned_basic'], 2); ?></td>
                            <td class="text-end text-primary">
                                <?php echo number_format($r['ot_pay'], 2); ?>
                                <?php if ($r['ot_hours'] > 0): ?>
                                    <br><small class="text-muted">(<?php echo number_format($r['ot_hours'], 1); ?>h)</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-success"><?php echo number_format($r['allowance'], 2); ?></td>
                            <td class="text-end text-danger"><?php echo number_format($r['advance'], 2); ?></td>
                            <td class="text-end text-danger"><?php echo number_format($r['food'], 2); ?></td>
                            <td class="text-end text-danger"><?php echo number_format($r['epf'], 2); ?></td>
                            <td class="text-end text-danger"><?php echo number_format($r['other_ded'], 2); ?></td>
                            <td class="text-end text-success fw-bold fs-6"><?php echo number_format($r['net'], 2); ?></td>
                            <td class="text-center">
                                <?php if ($r['is_paid']): ?>
                                    <span class="badge bg-success small">PAID</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark small">PENDING</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="grand-total-row">
                    <td colspan="4" class="text-end text-uppercase fw-bold">Grand Totals (LKR):</td>
                    <td class="text-end"><?php echo number_format($totals['basic'], 2); ?></td>
                    <td class="text-center">-</td>
                    <td class="text-end"><?php echo number_format($totals['earned_basic'], 2); ?></td>
                    <td class="text-end text-primary"><?php echo number_format($totals['ot_pay'], 2); ?></td>
                    <td class="text-end text-success"><?php echo number_format($totals['allowance'], 2); ?></td>
                    <td class="text-end text-danger"><?php echo number_format($totals['advance'], 2); ?></td>
                    <td class="text-end text-danger"><?php echo number_format($totals['food'], 2); ?></td>
                    <td class="text-end text-danger"><?php echo number_format($totals['epf'], 2); ?></td>
                    <td class="text-end text-danger"><?php echo number_format($totals['other_ded'], 2); ?></td>
                    <td class="text-end text-success fs-6 fw-bold"><?php echo number_format($totals['net'], 2); ?></td>
                    <td class="text-center">-</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Summary Box -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="p-3 border rounded bg-light text-center">
                <small class="text-muted d-block text-uppercase fw-bold">Total Employees</small>
                <strong class="fs-4 text-dark"><?php echo count($rows); ?> Staff</strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 border rounded bg-light text-center">
                <small class="text-muted d-block text-uppercase fw-bold">Total Gross Earnings</small>
                <strong class="fs-4 text-primary">Rs. <?php echo number_format($totals['gross'], 2); ?></strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 border rounded bg-light text-center">
                <small class="text-muted d-block text-uppercase fw-bold">Total Deductions</small>
                <strong class="fs-4 text-danger">Rs. <?php echo number_format($totals['total_ded'], 2); ?></strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 border rounded bg-dark text-white text-center">
                <small class="text-white-50 d-block text-uppercase fw-bold">Total Net Monthly Payroll</small>
                <strong class="fs-4 text-warning">Rs. <?php echo number_format($totals['net'], 2); ?></strong>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="row pt-4 mt-4 border-top">
        <div class="col-4 text-center">
            <div class="border-bottom mx-auto mb-2" style="width: 180px; height: 35px;"></div>
            <small class="text-muted d-block fw-bold">Prepared By (Payroll Clerk)</small>
            <small class="text-muted"><?php echo htmlspecialchars($processedBy ?: 'System Admin'); ?></small>
        </div>
        <div class="col-4 text-center">
            <div class="border-bottom mx-auto mb-2" style="width: 180px; height: 35px;"></div>
            <small class="text-muted d-block fw-bold">Verified By (Manager)</small>
        </div>
        <div class="col-4 text-center">
            <div class="border-bottom mx-auto mb-2" style="width: 180px; height: 35px;"></div>
            <small class="text-muted d-block fw-bold">Approved By (Director/Owner)</small>
        </div>
    </div>

    <div class="text-center text-muted small mt-4 pt-2 border-top">
        Printed on <?php echo date('Y-m-d H:i:s'); ?> | MLB Bakery Management System
    </div>
</div>

</body>
</html>
