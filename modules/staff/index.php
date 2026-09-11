<?php
// modules/staff/index.php - Staff Members Directory
require_once __DIR__ . '/../../includes/header.php';

// Accessible by Admin and Owner
requireRole(['admin', 'owner']);

$db = getDB();

// Fetch all staff members
$stmt = $db->query("SELECT * FROM staff ORDER BY id ASC");
$staffList = $stmt->fetchAll();

// Calculate staff stats
$totalStaff = count($staffList);
$activeStaff = 0;
$totalMonthlyBasic = 0;
foreach ($staffList as $s) {
    if ($s['status'] === 'active') {
        $activeStaff++;
        $totalMonthlyBasic += (float)$s['basic_salary'];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-users text-warning me-2"></i> Staff Members Management</h4>
        <p class="text-muted small mb-0">Manage bakery employee profiles, basic salaries, daily rates, and overtime rates.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>modules/staff/advances.php" class="btn btn-outline-success font-weight-bold">
            <i class="fa-solid fa-hand-holding-dollar me-1"></i> Issue Salary Advance
        </a>
        <a href="<?php echo BASE_URL; ?>modules/staff/food_consumption.php" class="btn btn-outline-warning text-dark font-weight-bold">
            <i class="fa-solid fa-utensils me-1"></i> Log Staff Food Consumption
        </a>
        <a href="<?php echo BASE_URL; ?>modules/payroll/attendance.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-file-excel me-1 text-success"></i> Upload Attendance (.xlsx/.csv)
        </a>
        <a href="<?php echo BASE_URL; ?>modules/staff/add.php" class="btn btn-warning text-dark font-weight-bold">
            <i class="fa-solid fa-user-plus me-1"></i> Add Staff Member
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card bg-bakery-orange">
            <div>
                <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Active Staff</h6>
                <div class="stat-value text-white"><?php echo $activeStaff; ?> / <?php echo $totalStaff; ?></div>
            </div>
            <small class="text-white-50"><i class="fa-solid fa-id-card me-1"></i> Registered Employees</small>
            <i class="fa-solid fa-user-tie stat-icon"></i>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card bg-bakery-chocolate">
            <div>
                <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Monthly Basic Payroll</h6>
                <div class="stat-value text-white"><?php echo formatMoney($totalMonthlyBasic); ?></div>
            </div>
            <small class="text-white-50"><i class="fa-solid fa-money-bill-wave me-1"></i> Combined Basic Salaries</small>
            <i class="fa-solid fa-wallet stat-icon"></i>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card bg-bakery-teal">
            <div>
                <h6 class="text-white-50 text-uppercase fw-bold mb-1">Payroll Management</h6>
                <small class="text-white d-block">Process & Pay Monthly Salaries</small>
            </div>
            <a href="<?php echo BASE_URL; ?>modules/payroll/index.php" class="btn btn-light text-teal font-weight-bold w-100 mt-2 py-1" style="font-size: 0.85rem;">
                <i class="fa-solid fa-calculator me-1"></i> Go to Salary Payroll
            </a>
            <i class="fa-solid fa-file-invoice-dollar stat-icon"></i>
        </div>
    </div>
</div>

<!-- Staff List Table -->
<div class="card card-bakery p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>EMP #</th>
                    <th>Full Name</th>
                    <th>Designation</th>
                    <th>Phone / NIC</th>
                    <th>Basic Salary</th>
                    <th>Daily Rate</th>
                    <th>OT Rate / Hr</th>
                    <th>EPF No.</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($staffList)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No staff members added yet. Click "Add Staff Member" to get started.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($staffList as $staff): ?>
                        <tr>
                            <td><strong class="text-dark"><code><?php echo htmlspecialchars($staff['emp_number']); ?></code></strong></td>
                            <td>
                                <strong class="text-dark d-block"><?php echo htmlspecialchars($staff['name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="fa-solid fa-briefcase me-1 text-muted"></i> <?php echo htmlspecialchars($staff['designation']); ?>
                                </span>
                            </td>
                            <td>
                                <div><i class="fa-solid fa-phone me-1 text-muted text-xs"></i> <?php echo htmlspecialchars($staff['phone'] ?: 'N/A'); ?></div>
                                <small class="text-muted"><i class="fa-solid fa-id-card me-1 text-xs"></i> <?php echo htmlspecialchars($staff['nic'] ?: 'N/A'); ?></small>
                            </td>
                            <td><strong class="text-success"><?php echo formatMoney($staff['basic_salary']); ?></strong></td>
                            <td><?php echo formatMoney($staff['daily_rate']); ?></td>
                            <td><?php echo formatMoney($staff['ot_rate_per_hour']); ?></td>
                            <td><code><?php echo htmlspecialchars($staff['epf_no'] ?: 'N/A'); ?></code></td>
                            <td>
                                <?php if ($staff['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?php echo BASE_URL; ?>modules/staff/edit.php?id=<?php echo $staff['id']; ?>" class="btn btn-sm btn-outline-secondary me-1">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
