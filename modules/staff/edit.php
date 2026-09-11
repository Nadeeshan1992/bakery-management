<?php
// modules/staff/edit.php - Edit Bakery Staff Profile
require_once __DIR__ . '/../../includes/header.php';

requireRole(['admin', 'owner']);

$db = getDB();
$staffId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$staffId]);
$staff = $stmt->fetch();

if (!$staff) {
    setFlash('danger', 'Staff member not found.');
    header('Location: ' . BASE_URL . 'modules/staff/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empNumber     = trim($_POST['emp_number'] ?? '');
    $name          = trim($_POST['name'] ?? '');
    $designation   = trim($_POST['designation'] ?? 'Staff');
    $phone         = trim($_POST['phone'] ?? '');
    $nic           = trim($_POST['nic'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $basicSalary     = (float)($_POST['basic_salary'] ?? 0);
    $fixedAllowance  = (float)($_POST['fixed_allowance'] ?? 0);
    $dailyRate       = (float)($_POST['daily_rate'] ?? 0);
    $otRatePerHour   = (float)($_POST['ot_rate_per_hour'] ?? 0);
    $epfNo           = trim($_POST['epf_no'] ?? '');
    $bankName        = trim($_POST['bank_name'] ?? '');
    $accountNo       = trim($_POST['account_no'] ?? '');
    $status          = trim($_POST['status'] ?? 'active');

    if (empty($empNumber) || empty($name)) {
        $error = "Please fill in all required fields (Employee Number and Full Name).";
    } else {
        try {
            // Check EMP Code uniqueness for other records
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM staff WHERE emp_number = ? AND id != ?");
            $stmtCheck->execute([$empNumber, $staffId]);
            if ($stmtCheck->fetchColumn() > 0) {
                $error = "Employee Number '$empNumber' belongs to another staff member.";
            } else {
                $stmtUpdate = $db->prepare("
                    UPDATE staff SET 
                        emp_number = ?, name = ?, designation = ?, phone = ?, nic = ?, address = ?,
                        basic_salary = ?, fixed_allowance = ?, daily_rate = ?, ot_rate_per_hour = ?, epf_no = ?, bank_name = ?, account_no = ?, status = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([
                    $empNumber, $name, $designation, $phone, $nic, $address,
                    $basicSalary, $fixedAllowance, $dailyRate, $otRatePerHour, $epfNo, $bankName, $accountNo, $status, $staffId
                ]);

                setFlash('success', "Staff profile for '$name' updated successfully!");
                header('Location: ' . BASE_URL . 'modules/staff/index.php');
                exit;
            }
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-user-pen text-warning me-2"></i> Edit Staff Profile</h4>
        <p class="text-muted small mb-0">Update employee details, rates, and status for <?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['emp_number']); ?>).</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/staff/index.php" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Staff Directory
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card card-bakery p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4">
                    <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-id-card text-warning me-2"></i> Personal Details</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="emp_number" class="form-label fw-bold">Employee Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="emp_number" name="emp_number" value="<?php echo htmlspecialchars($_POST['emp_number'] ?? $staff['emp_number']); ?>" required>
                    </div>

                    <div class="col-md-5">
                        <label for="name" class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $staff['name']); ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label for="designation" class="form-label fw-bold">Designation <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="designation" name="designation" value="<?php echo htmlspecialchars($_POST['designation'] ?? $staff['designation']); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="phone" class="form-label fw-bold">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? $staff['phone']); ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="nic" class="form-label fw-bold">NIC Number</label>
                        <input type="text" class="form-control" id="nic" name="nic" value="<?php echo htmlspecialchars($_POST['nic'] ?? $staff['nic']); ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="epf_no" class="form-label fw-bold">EPF Number</label>
                        <input type="text" class="form-control" id="epf_no" name="epf_no" value="<?php echo htmlspecialchars($_POST['epf_no'] ?? $staff['epf_no']); ?>">
                    </div>

                    <div class="col-12">
                        <label for="address" class="form-label fw-bold">Residential Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? $staff['address']); ?>">
                    </div>
                </div>

                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-calculator text-success me-2"></i> Salary & Overtime Rates (LKR)</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="basic_salary" class="form-label fw-bold">Monthly Basic Salary (Rs.)</label>
                        <input type="number" step="0.01" class="form-control" id="basic_salary" name="basic_salary" value="<?php echo htmlspecialchars($_POST['basic_salary'] ?? $staff['basic_salary']); ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="fixed_allowance" class="form-label fw-bold">Fixed Monthly Allowance (Rs.)</label>
                        <input type="number" step="0.01" class="form-control" id="fixed_allowance" name="fixed_allowance" value="<?php echo htmlspecialchars($_POST['fixed_allowance'] ?? $staff['fixed_allowance']); ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="daily_rate" class="form-label fw-bold">Daily Rate (Rs.)</label>
                        <input type="number" step="0.01" class="form-control" id="daily_rate" name="daily_rate" value="<?php echo htmlspecialchars($_POST['daily_rate'] ?? $staff['daily_rate']); ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="ot_rate_per_hour" class="form-label fw-bold">OT Rate / Hour (Rs.)</label>
                        <input type="number" step="0.01" class="form-control" id="ot_rate_per_hour" name="ot_rate_per_hour" value="<?php echo htmlspecialchars($_POST['ot_rate_per_hour'] ?? $staff['ot_rate_per_hour']); ?>">
                    </div>
                </div>

                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-building-columns text-primary me-2"></i> Bank & Account Status</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-5">
                        <label for="bank_name" class="form-label fw-bold">Bank Name</label>
                        <input type="text" class="form-control" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($_POST['bank_name'] ?? $staff['bank_name']); ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="account_no" class="form-label fw-bold">Account Number</label>
                        <input type="text" class="form-control" id="account_no" name="account_no" value="<?php echo htmlspecialchars($_POST['account_no'] ?? $staff['account_no']); ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="status" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo ($staff['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($staff['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo BASE_URL; ?>modules/staff/index.php" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-warning text-dark font-weight-bold">
                        <i class="fa-solid fa-check me-1"></i> Update Staff Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
