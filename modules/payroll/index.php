<?php
// modules/payroll/index.php - Monthly Salary Payroll Processing
require_once __DIR__ . '/../../includes/header.php';

requireRole(['admin', 'owner']);

$db = getDB();
$selectedMonth = $_GET['month'] ?? date('Y-m');

// Fetch active staff
$stmtStaff = $db->query("SELECT * FROM staff WHERE status = 'active' ORDER BY emp_number ASC");
$staffMembers = $stmtStaff->fetchAll();

// Fetch already processed salaries for selected month if any
$stmtSalaries = $db->prepare("SELECT * FROM staff_salaries WHERE payroll_month = ?");
$stmtSalaries->execute([$selectedMonth]);
$existingSalaries = [];
foreach ($stmtSalaries->fetchAll() as $sRow) {
    $existingSalaries[$sRow['staff_id']] = $sRow;
}

// Calculate attendance summaries per staff member for the selected month
$attendanceSummaries = [];
$stmtAtt = $db->prepare("
    SELECT staff_id, 
           SUM(CASE WHEN status = 'present' THEN 1 WHEN status = 'half_day' THEN 0.5 ELSE 0 END) as present_days,
           SUM(ot_hours) as total_ot_hours
    FROM staff_attendance 
    WHERE DATE_FORMAT(attendance_date, '%Y-%m') = ?
    GROUP BY staff_id
");
$stmtAtt->execute([$selectedMonth]);
// Calculate food consumption totals per staff member for selected month
$foodSummaries = [];
$stmtFood = $db->prepare("
    SELECT staff_id, COALESCE(SUM(total_price), 0) as food_total 
    FROM staff_food_consumption 
    WHERE DATE_FORMAT(consumption_date, '%Y-%m') = ? 
    GROUP BY staff_id
");
$stmtFood->execute([$selectedMonth]);
foreach ($stmtFood->fetchAll() as $fRow) {
    $foodSummaries[$fRow['staff_id']] = (float)$fRow['food_total'];
}

// Calculate salary advance totals per staff member for selected month
$advanceSummaries = [];
$stmtAdv = $db->prepare("
    SELECT staff_id, COALESCE(SUM(advance_amount), 0) as advance_total 
    FROM staff_advances 
    WHERE DATE_FORMAT(advance_date, '%Y-%m') = ? 
    GROUP BY staff_id
");
$stmtAdv->execute([$selectedMonth]);
foreach ($stmtAdv->fetchAll() as $aRow) {
    $advanceSummaries[$aRow['staff_id']] = (float)$aRow['advance_total'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-calculator text-warning me-2"></i> Monthly Salary Payroll</h4>
        <p class="text-muted small mb-0">Compute staff salaries, overtime pay, EPF deductions, and issue printable payslips.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>modules/payroll/summary_sheet.php?month=<?php echo htmlspecialchars($selectedMonth); ?>" class="btn btn-outline-primary" target="_blank">
            <i class="fa-solid fa-print me-1"></i> Master Salary Sheet
        </a>
        <a href="<?php echo BASE_URL; ?>modules/payroll/attendance.php" class="btn btn-outline-success">
            <i class="fa-solid fa-file-excel me-1"></i> Upload Attendance (.xlsx)
        </a>
        <a href="<?php echo BASE_URL; ?>modules/staff/index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-users me-1"></i> Staff Directory
        </a>
    </div>
</div>

<!-- Month Selector Card -->
<div class="card card-bakery p-3 mb-4">
    <form method="GET" action="" class="row g-3 align-items-center">
        <div class="col-auto">
            <label for="month" class="form-label mb-0 font-weight-bold"><i class="fa-solid fa-calendar-days text-primary me-1"></i> Select Payroll Month:</label>
        </div>
        <div class="col-auto">
            <input type="month" id="month" name="month" class="form-control" value="<?php echo htmlspecialchars($selectedMonth); ?>" onchange="this.form.submit()">
        </div>
        <div class="col-auto text-muted text-sm ms-auto">
            Payroll Period: <strong><?php echo date('F 01, Y', strtotime($selectedMonth . '-01')); ?></strong> to <strong><?php echo date('F t, Y', strtotime($selectedMonth . '-01')); ?></strong>
        </div>
    </form>
</div>

<!-- Payroll Processing Form -->
<form method="POST" action="<?php echo BASE_URL; ?>modules/payroll/process.php">
    <input type="hidden" name="payroll_month" value="<?php echo htmlspecialchars($selectedMonth); ?>">

    <div class="card card-bakery p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>EMP #</th>
                        <th>Staff Name</th>
                        <th>Basic Salary</th>
                        <th>Days Worked</th>
                        <th>Earned Basic</th>
                        <th>OT Hours / Pay</th>
                        <th>Allowances</th>
                        <th>Deductions / EPF</th>
                        <th>Net Salary (LKR)</th>
                        <th class="text-end">Status / Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($staffMembers)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No active staff members found. Add staff in Staff Directory first.</td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $totalNetPayroll = 0;
                        foreach ($staffMembers as $staff): 
                            $staffId = $staff['id'];
                            $att = $attendanceSummaries[$staffId] ?? null;
                            $existing = $existingSalaries[$staffId] ?? null;

                            if ($existing !== null && isset($existing['present_days'])) {
                                $presentDays = (float)$existing['present_days'];
                            } elseif ($att !== null && isset($att['present_days'])) {
                                $presentDays = (float)$att['present_days'];
                            } else {
                                $presentDays = 30.0; // Default to full month working days
                            }

                            $totalOtHours = (float)($existing['total_ot_hours'] ?? ($att['total_ot_hours'] ?? 0));
                            
                            $basicSalary = (float)$staff['basic_salary'];
                            $dailyRate = (float)$staff['daily_rate'];
                            $otRate = (float)$staff['ot_rate_per_hour'];

                            // Basic calculation (if daily rate exists use daily rate * present days, else proportional basic)
                            if ($dailyRate > 0) {
                                $earnedBasic = $dailyRate * $presentDays;
                            } else {
                                $earnedBasic = ($basicSalary / 30) * $presentDays;
                            }

                            $otPay = $totalOtHours * $otRate;
                            $allowance = (float)($existing['allowances'] ?? ($staff['fixed_allowance'] ?? 0));
                            $deduction = (float)($existing['deductions'] ?? 0);
                            $liveAdvanceTotal = (float)($advanceSummaries[$staffId] ?? 0);
                            $advanceDeduction = ($liveAdvanceTotal > 0) ? $liveAdvanceTotal : (float)($existing['advance_deduction'] ?? 0);
                            $liveFoodTotal = (float)($foodSummaries[$staffId] ?? 0);
                            $foodDeduction = ($liveFoodTotal > 0) ? $liveFoodTotal : (float)($existing['food_deduction'] ?? 0);
                            $epfDeduction = (float)($existing['epf_deduction'] ?? ($earnedBasic * 0.08)); // Default 8% EPF

                            $netSalary = max(0, $earnedBasic + $otPay + $allowance - $deduction - $advanceDeduction - $foodDeduction - $epfDeduction);
                            $totalNetPayroll += $netSalary;
                        ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($staff['emp_number']); ?></code></td>
                                <td>
                                    <strong class="text-dark d-block"><?php echo htmlspecialchars($staff['name']); ?></strong>
                                    <small class="text-muted"><?php echo htmlspecialchars($staff['designation']); ?></small>
                                </td>
                                <td><?php echo formatMoney($basicSalary); ?></td>
                                <td>
                                    <input type="number" step="0.5" name="present_days[<?php echo $staffId; ?>]" class="form-control form-control-sm text-center font-weight-bold" style="width: 75px;" value="<?php echo $presentDays; ?>" oninput="recalculateRow(<?php echo $staffId; ?>)">
                                </td>
                                <td>
                                    <span id="earned_basic_<?php echo $staffId; ?>" class="fw-bold"><?php echo formatMoney($earnedBasic); ?></span>
                                    <input type="hidden" id="daily_rate_val_<?php echo $staffId; ?>" value="<?php echo $dailyRate > 0 ? $dailyRate : ($basicSalary / 30); ?>">
                                </td>
                                <td>
                                    <div><strong id="ot_pay_<?php echo $staffId; ?>" class="text-primary"><?php echo formatMoney($otPay); ?></strong></div>
                                    <small class="text-muted"><span id="ot_hrs_<?php echo $staffId; ?>"><?php echo number_format($totalOtHours, 1); ?></span> hrs @ Rs.<?php echo $otRate; ?>/hr</small>
                                    <input type="hidden" name="total_ot_hours[<?php echo $staffId; ?>]" value="<?php echo $totalOtHours; ?>">
                                    <input type="hidden" id="ot_rate_val_<?php echo $staffId; ?>" value="<?php echo $otRate; ?>">
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="allowances[<?php echo $staffId; ?>]" id="allowance_<?php echo $staffId; ?>" class="form-control form-control-sm" style="width: 100px;" value="<?php echo $allowance; ?>" oninput="recalculateRow(<?php echo $staffId; ?>)">
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="deductions[<?php echo $staffId; ?>]" id="deduction_<?php echo $staffId; ?>" class="form-control form-control-sm mb-1" style="width: 100px;" value="<?php echo $deduction; ?>" placeholder="Other" oninput="recalculateRow(<?php echo $staffId; ?>)">
                                    <small class="text-muted d-block">Advance: <span id="adv_text_<?php echo $staffId; ?>" class="text-danger fw-bold">Rs. <?php echo number_format($advanceDeduction, 2); ?></span></small>
                                    <input type="hidden" name="advance_deduction[<?php echo $staffId; ?>]" id="adv_val_<?php echo $staffId; ?>" value="<?php echo $advanceDeduction; ?>">
                                    <small class="text-muted d-block">Food: <span id="food_text_<?php echo $staffId; ?>" class="text-danger">Rs. <?php echo number_format($foodDeduction, 2); ?></span></small>
                                    <input type="hidden" name="food_deduction[<?php echo $staffId; ?>]" id="food_val_<?php echo $staffId; ?>" value="<?php echo $foodDeduction; ?>">
                                    <small class="text-muted d-block">EPF(8%): <span id="epf_text_<?php echo $staffId; ?>"><?php echo formatMoney($epfDeduction); ?></span></small>
                                    <input type="hidden" name="epf_deduction[<?php echo $staffId; ?>]" id="epf_val_<?php echo $staffId; ?>" value="<?php echo $epfDeduction; ?>">
                                </td>
                                <td>
                                    <strong id="net_salary_<?php echo $staffId; ?>" class="fs-6 text-success"><?php echo formatMoney($netSalary); ?></strong>
                                </td>
                                <td class="text-end">
                                    <?php if ($existing && $existing['payment_status'] === 'paid'): ?>
                                        <span class="badge bg-success mb-1 d-block"><i class="fa-solid fa-check-double me-1"></i> Paid</span>
                                        <a href="<?php echo BASE_URL; ?>modules/payroll/payslip.php?id=<?php echo $existing['id']; ?>" class="btn btn-sm btn-outline-dark" target="_blank">
                                            <i class="fa-solid fa-print me-1"></i> Payslip
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark mb-1 d-block">Pending</span>
                                        <?php if ($existing): ?>
                                            <a href="<?php echo BASE_URL; ?>modules/payroll/payslip.php?id=<?php echo $existing['id']; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                                <i class="fa-solid fa-file-invoice me-1"></i> View Slip
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark mb-0">Total Estimated Net Payroll: <span id="total_net_display" class="text-success"><?php echo formatMoney($totalNetPayroll ?? 0); ?></span></h5>
            </div>
            <button type="submit" class="btn btn-warning text-dark font-weight-bold btn-lg px-4">
                <i class="fa-solid fa-check-double me-2"></i> Process & Save Payroll
            </button>
        </div>
    </div>
</form>

<script>
function recalculateRow(staffId) {
    const presentDays = parseFloat(document.querySelector(`input[name="present_days[${staffId}]"]`).value) || 0;
    const dailyRate = parseFloat(document.getElementById(`daily_rate_val_${staffId}`).value) || 0;
    const otHours = parseFloat(document.querySelector(`input[name="total_ot_hours[${staffId}]"]`).value) || 0;
    const otRate = parseFloat(document.getElementById(`ot_rate_val_${staffId}`).value) || 0;
    const allowance = parseFloat(document.getElementById(`allowance_${staffId}`).value) || 0;
    const deduction = parseFloat(document.getElementById(`deduction_${staffId}`).value) || 0;
    const advanceDeduction = parseFloat(document.getElementById(`adv_val_${staffId}`).value) || 0;
    const foodDeduction = parseFloat(document.getElementById(`food_val_${staffId}`).value) || 0;

    const earnedBasic = dailyRate * presentDays;
    const otPay = otHours * otRate;
    const epfDeduction = earnedBasic * 0.08;
    const netSalary = Math.max(0, earnedBasic + otPay + allowance - deduction - advanceDeduction - foodDeduction - epfDeduction);

    document.getElementById(`earned_basic_${staffId}`).innerText = 'Rs. ' + earnedBasic.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById(`ot_pay_${staffId}`).innerText = 'Rs. ' + otPay.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById(`epf_text_${staffId}`).innerText = 'Rs. ' + epfDeduction.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById(`epf_val_${staffId}`).value = epfDeduction;
    document.getElementById(`net_salary_${staffId}`).innerText = 'Rs. ' + netSalary.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
