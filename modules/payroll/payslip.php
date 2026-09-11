<?php
// modules/payroll/payslip.php - Printable Staff Salary Voucher / Payslip
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['admin', 'owner']);

$db = getDB();
$salaryId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT s.*, st.emp_number, st.name as staff_name, st.designation, st.phone, st.nic, st.epf_no, st.bank_name, st.account_no, u.full_name as processed_by_name
    FROM staff_salaries s
    JOIN staff st ON s.staff_id = st.id
    LEFT JOIN users u ON s.created_by = u.id
    WHERE s.id = ?
");
$stmt->execute([$salaryId]);
$slip = $stmt->fetch();

if (!$slip) {
    die("Payslip record not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Payslip - <?php echo htmlspecialchars($slip['emp_number'] . ' - ' . $slip['staff_name']); ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #212529;
        }
        .payslip-container {
            max-width: 750px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            padding: 40px;
            border: 1px solid #e9ecef;
        }
        .header-logo {
            height: 70px;
            border-radius: 8px;
        }
        .table-payroll th {
            background-color: #f8f9fa;
        }
        @media print {
            body { background: #ffffff; }
            .payslip-container { box-shadow: none; border: none; padding: 0; margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container text-end mt-3 mb-2 no-print" style="max-width: 750px;">
    <button onclick="window.print()" class="btn btn-warning text-dark font-weight-bold shadow-sm">
        <i class="fa-solid fa-print me-1"></i> Print Salary Voucher
    </button>
</div>

<div class="payslip-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <img src="<?php echo BASE_URL; ?>assets/images/mlb_logo.jpg" alt="MLB Logo" class="header-logo">
            <div>
                <h3 class="fw-bold mb-0 text-dark">MLB Bakery POS</h3>
                <small class="text-muted">No. 124 Bakery Road, Main Street | Tel: 077-1234567</small>
            </div>
        </div>
        <div class="text-end">
            <h5 class="fw-bold text-uppercase text-warning mb-1">Salary Slip</h5>
            <div class="badge bg-light text-dark border">Period: <?php echo date('F Y', strtotime($slip['payroll_month'] . '-01')); ?></div>
        </div>
    </div>

    <!-- Staff Info Grid -->
    <div class="row g-3 bg-light p-3 rounded mb-4 border">
        <div class="col-6">
            <small class="text-muted d-block">Employee Name:</small>
            <strong class="fs-6 text-dark"><?php echo htmlspecialchars($slip['staff_name']); ?></strong>
        </div>
        <div class="col-6 text-end">
            <small class="text-muted d-block">Employee Code:</small>
            <code class="fs-6"><?php echo htmlspecialchars($slip['emp_number']); ?></code>
        </div>
        <div class="col-4">
            <small class="text-muted d-block">Designation:</small>
            <strong><?php echo htmlspecialchars($slip['designation']); ?></strong>
        </div>
        <div class="col-4 text-center">
            <small class="text-muted d-block">EPF No.:</small>
            <strong><?php echo htmlspecialchars($slip['epf_no'] ?: 'N/A'); ?></strong>
        </div>
        <div class="col-4 text-end">
            <small class="text-muted d-block">NIC Number:</small>
            <strong><?php echo htmlspecialchars($slip['nic'] ?: 'N/A'); ?></strong>
        </div>
    </div>

    <!-- Attendance Breakdown -->
    <div class="row text-center mb-4 g-2">
        <div class="col-4">
            <div class="border rounded p-2 bg-white">
                <small class="text-muted d-block">Days Worked</small>
                <strong class="fs-5 text-dark"><?php echo number_format($slip['present_days'], 1); ?> Days</strong>
            </div>
        </div>
        <div class="col-4">
            <div class="border rounded p-2 bg-white">
                <small class="text-muted d-block">Overtime Hours</small>
                <strong class="fs-5 text-primary"><?php echo number_format($slip['total_ot_hours'], 1); ?> Hours</strong>
            </div>
        </div>
        <div class="col-4">
            <div class="border rounded p-2 bg-white">
                <small class="text-muted d-block">Payment Status</small>
                <strong class="fs-5 text-success">PAID</strong>
            </div>
        </div>
    </div>

    <!-- Earnings & Deductions Table -->
    <div class="row g-4 mb-4">
        <!-- Earnings -->
        <div class="col-6">
            <h6 class="fw-bold text-success border-bottom pb-2 mb-2"><i class="fa-solid fa-plus-circle me-1"></i> Earnings & Allowances</h6>
            <table class="table table-sm table-borderless">
                <tr>
                    <td class="text-muted">Basic Monthly Salary:</td>
                    <td class="text-end"><?php echo formatMoney($slip['basic_salary']); ?></td>
                </tr>
                <tr>
                    <td><strong>Earned Basic Pay:</strong></td>
                    <td class="text-end"><strong><?php echo formatMoney($slip['earned_basic']); ?></strong></td>
                </tr>
                <tr>
                    <td>Overtime Pay:</td>
                    <td class="text-end text-primary">+ <?php echo formatMoney($slip['ot_pay']); ?></td>
                </tr>
                <tr>
                    <td>Special Allowances:</td>
                    <td class="text-end text-success">+ <?php echo formatMoney($slip['allowances']); ?></td>
                </tr>
                <tr class="border-top">
                    <td><strong>Gross Earnings:</strong></td>
                    <td class="text-end"><strong><?php echo formatMoney($slip['earned_basic'] + $slip['ot_pay'] + $slip['allowances']); ?></strong></td>
                </tr>
            </table>
        </div>

        <!-- Deductions -->
        <div class="col-6">
            <h6 class="fw-bold text-danger border-bottom pb-2 mb-2"><i class="fa-solid fa-minus-circle me-1"></i> Deductions & EPF</h6>
            <table class="table table-sm table-borderless">
                <tr>
                    <td>EPF Employee (8%):</td>
                    <td class="text-end text-danger">- <?php echo formatMoney($slip['epf_deduction']); ?></td>
                </tr>
                <tr>
                    <td>Salary Advance Taken:</td>
                    <td class="text-end text-danger">- <?php echo formatMoney($slip['advance_deduction']); ?></td>
                </tr>
                <tr>
                    <td>Bakery Food Consumption:</td>
                    <td class="text-end text-danger">- <?php echo formatMoney($slip['food_deduction']); ?></td>
                </tr>
                <tr>
                    <td>Other / Loss Deductions:</td>
                    <td class="text-end text-danger">- <?php echo formatMoney($slip['deductions']); ?></td>
                </tr>
                <tr class="border-top">
                    <td><strong>Total Deductions:</strong></td>
                    <td class="text-end text-danger"><strong>- <?php echo formatMoney($slip['epf_deduction'] + $slip['advance_deduction'] + $slip['food_deduction'] + $slip['deductions']); ?></strong></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Total Net Salary Card -->
    <div class="p-3 bg-dark text-white rounded d-flex justify-content-between align-items-center mb-5">
        <div>
            <h5 class="mb-0 text-white-50 text-uppercase fw-bold" style="font-size: 0.9rem;">Net Payable Salary</h5>
            <small class="text-white-50">Transferred / Paid on <?php echo date('M d, Y', strtotime($slip['paid_date'])); ?></small>
        </div>
        <div class="fs-2 fw-bold text-warning"><?php echo formatMoney($slip['net_salary']); ?></div>
    </div>

    <!-- Signatures -->
    <div class="row pt-4 mt-4 border-top">
        <div class="col-6 text-center">
            <div class="border-bottom mx-auto" style="width: 180px; height: 40px;"></div>
            <small class="text-muted d-block mt-2">Employee Signature</small>
        </div>
        <div class="col-6 text-center">
            <div class="border-bottom mx-auto" style="width: 180px; height: 40px;"></div>
            <small class="text-muted d-block mt-2">Authorized Manager (<?php echo htmlspecialchars($slip['processed_by_name'] ?: 'Manager'); ?>)</small>
        </div>
    </div>
</div>

</body>
</html>
