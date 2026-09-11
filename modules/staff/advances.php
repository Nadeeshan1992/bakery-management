<?php
// modules/staff/advances.php - Issue & Manage Staff Salary Advances
require_once __DIR__ . '/../../includes/header.php';

requireRole(['admin', 'owner']);

$db = getDB();
$message = '';
$error = '';

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    try {
        $stmtEntry = $db->prepare("SELECT a.*, s.name as staff_name FROM staff_advances a JOIN staff s ON a.staff_id = s.id WHERE a.id = ?");
        $stmtEntry->execute([$deleteId]);
        $entry = $stmtEntry->fetch();

        if ($entry) {
            $stmtDel = $db->prepare("DELETE FROM staff_advances WHERE id = ?");
            $stmtDel->execute([$deleteId]);

            setFlash('success', "Salary advance record of Rs. " . number_format($entry['advance_amount'], 2) . " for " . $entry['staff_name'] . " deleted successfully.");
        } else {
            setFlash('danger', "Record not found.");
        }
    } catch (Exception $e) {
        setFlash('danger', "Error deleting record: " . $e->getMessage());
    }
    $monthParam = $_GET['month'] ?? date('Y-m');
    header('Location: ' . BASE_URL . 'modules/staff/advances.php?month=' . urlencode($monthParam));
    exit;
}

// Fetch record for Editing if edit_id is provided
$editingAdvance = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $stmtE = $db->prepare("SELECT * FROM staff_advances WHERE id = ?");
    $stmtE->execute([$editId]);
    $editingAdvance = $stmtE->fetch();
}

// Fetch Active Staff Members
$stmtStaff = $db->query("SELECT id, emp_number, name, designation, basic_salary FROM staff WHERE status = 'active' ORDER BY emp_number ASC");
$staffMembers = $stmtStaff->fetchAll();

// Process Salary Advance Submission (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId        = (int)($_POST['edit_id'] ?? 0);
    $staffId       = (int)($_POST['staff_id'] ?? 0);
    $advanceAmount = (float)($_POST['advance_amount'] ?? 0);
    $advanceDate   = trim($_POST['advance_date'] ?? date('Y-m-d'));
    $notes         = trim($_POST['notes'] ?? '');

    if ($staffId <= 0 || $advanceAmount <= 0) {
        $error = "Please select a valid staff member and enter a positive advance amount.";
    } else {
        try {
            $userId = getCurrentUserId();
            $db->beginTransaction();

            if ($editId > 0) {
                // Update Existing Advance Record
                $stmtUpd = $db->prepare("
                    UPDATE staff_advances 
                    SET staff_id = ?, advance_amount = ?, advance_date = ?, notes = ? 
                    WHERE id = ?
                ");
                $stmtUpd->execute([$staffId, $advanceAmount, $advanceDate, $notes, $editId]);
                $db->commit();
                setFlash('success', "Salary advance entry updated successfully! Monthly paysheet deductions updated.");
                header('Location: ' . BASE_URL . 'modules/staff/advances.php?month=' . urlencode(date('Y-m', strtotime($advanceDate))));
                exit;
            } else {
                // Create New Advance Record
                $stmtInsert = $db->prepare("
                    INSERT INTO staff_advances (staff_id, advance_amount, advance_date, notes, created_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtInsert->execute([$staffId, $advanceAmount, $advanceDate, $notes, $userId]);
                $db->commit();
                setFlash('success', "Salary advance of Rs. " . number_format($advanceAmount, 2) . " issued to staff member. Added to monthly paysheet!");
                header('Location: ' . BASE_URL . 'modules/staff/advances.php?month=' . urlencode(date('Y-m', strtotime($advanceDate))));
                exit;
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch Salary Advance History for selected month
$selectedMonth = $_GET['month'] ?? date('Y-m');
$filterStaff = (int)($_GET['staff_id'] ?? 0);

$query = "
    SELECT a.*, s.emp_number, s.name as staff_name, s.designation, u.full_name as created_by_name 
    FROM staff_advances a 
    JOIN staff s ON a.staff_id = s.id 
    LEFT JOIN users u ON a.created_by = u.id 
    WHERE DATE_FORMAT(a.advance_date, '%Y-%m') = ?
";
$params = [$selectedMonth];
if ($filterStaff > 0) {
    $query .= " AND a.staff_id = ?";
    $params[] = $filterStaff;
}
$query .= " ORDER BY a.advance_date DESC, a.id DESC";

$stmtLogs = $db->prepare($query);
$stmtLogs->execute($params);
$advanceLogs = $stmtLogs->fetchAll();

// Calculate total monthly advances issued
$totalMonthlyAdvances = 0;
foreach ($advanceLogs as $al) {
    $totalMonthlyAdvances += (float)$al['advance_amount'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-hand-holding-dollar text-warning me-2"></i> Staff Salary Advances</h4>
        <p class="text-muted small mb-0">Issue salary advances to staff members (single or multiple times per month). Automatically deducted from monthly paysheets.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>modules/payroll/index.php" class="btn btn-warning text-dark font-weight-bold">
            <i class="fa-solid fa-calculator me-1"></i> Go to Salary Payroll
        </a>
        <a href="<?php echo BASE_URL; ?>modules/staff/index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-users me-1"></i> Staff Directory
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4">
        <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<!-- Issue / Edit Salary Advance Form -->
<div class="card card-bakery p-4 mb-4 <?php echo $editingAdvance ? 'border-warning' : ''; ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">
            <?php if ($editingAdvance): ?>
                <i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Salary Advance Record #<?php echo $editingAdvance['id']; ?>
            <?php else: ?>
                <i class="fa-solid fa-hand-holding-dollar text-success me-2"></i> Issue Salary Advance to Employee
            <?php endif; ?>
        </h5>
        <?php if ($editingAdvance): ?>
            <a href="<?php echo BASE_URL; ?>modules/staff/advances.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-xmark me-1"></i> Cancel Edit
            </a>
        <?php endif; ?>
    </div>

    <form method="POST" action="">
        <?php if ($editingAdvance): ?>
            <input type="hidden" name="edit_id" value="<?php echo $editingAdvance['id']; ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-5">
                <label for="staff_id" class="form-label fw-bold">Select Staff Member <span class="text-danger">*</span></label>
                <select class="form-select" id="staff_id" name="staff_id" required>
                    <option value="">-- Choose Employee --</option>
                    <?php 
                    $selectedStaff = $editingAdvance ? $editingAdvance['staff_id'] : '';
                    foreach ($staffMembers as $s): 
                    ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($selectedStaff == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['emp_number'] . ' - ' . $s['name'] . ' (' . $s['designation'] . ' - Basic: Rs.' . number_format($s['basic_salary'], 0) . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="advance_amount" class="form-label fw-bold">Advance Amount (Rs.) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" class="form-control" id="advance_amount" name="advance_amount" placeholder="e.g. 5000.00" value="<?php echo htmlspecialchars($editingAdvance ? $editingAdvance['advance_amount'] : ''); ?>" required>
            </div>

            <div class="col-md-4">
                <label for="advance_date" class="form-label fw-bold">Date Issued <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="advance_date" name="advance_date" value="<?php echo htmlspecialchars($editingAdvance ? $editingAdvance['advance_date'] : date('Y-m-d')); ?>" required>
            </div>

            <div class="col-12">
                <label for="notes" class="form-label fw-bold">Reason / Notes (Optional)</label>
                <input type="text" class="form-control" id="notes" name="notes" placeholder="e.g. Emergency medical advance / Personal advance" value="<?php echo htmlspecialchars($editingAdvance ? $editingAdvance['notes'] : ''); ?>">
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-end gap-2">
            <?php if ($editingAdvance): ?>
                <a href="<?php echo BASE_URL; ?>modules/staff/advances.php" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-warning text-dark font-weight-bold btn-lg">
                    <i class="fa-solid fa-check me-1"></i> Update Salary Advance
                </button>
            <?php else: ?>
                <button type="submit" class="btn btn-warning text-dark font-weight-bold btn-lg">
                    <i class="fa-solid fa-plus-circle me-1"></i> Issue Salary Advance
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- History Log Table -->
<div class="card card-bakery p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Issued Salary Advances History</h5>
            <small class="text-muted">Total Salary Advances for period: <strong class="text-danger"><?php echo formatMoney($totalMonthlyAdvances); ?></strong></small>
        </div>
        <form method="GET" action="" class="d-flex align-items-center gap-2">
            <input type="month" id="month" name="month" class="form-control form-control-sm" value="<?php echo htmlspecialchars($selectedMonth); ?>" onchange="this.form.submit()">
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date Issued</th>
                    <th>EMP Code</th>
                    <th>Staff Member</th>
                    <th>Designation</th>
                    <th>Advance Amount</th>
                    <th>Issued By</th>
                    <th>Notes</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($advanceLogs)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No salary advances issued for <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($advanceLogs as $log): ?>
                        <tr>
                            <td><strong><?php echo date('M d, Y', strtotime($log['advance_date'])); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($log['emp_number']); ?></code></td>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($log['staff_name']); ?></strong></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($log['designation']); ?></span></td>
                            <td><strong class="text-danger fs-6"><?php echo formatMoney($log['advance_amount']); ?></strong></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($log['created_by_name'] ?: 'Admin'); ?></small></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($log['notes'] ?: '-'); ?></small></td>
                            <td class="text-end text-nowrap">
                                <a href="<?php echo BASE_URL; ?>modules/staff/advances.php?edit_id=<?php echo $log['id']; ?>&month=<?php echo urlencode($selectedMonth); ?>" class="btn btn-sm btn-outline-secondary me-1">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="triggerDeleteModal(<?php echo $log['id']; ?>, '<?php echo htmlspecialchars(addslashes($log['staff_name'])); ?>', '<?php echo number_format($log['advance_amount'], 2); ?>', '<?php echo urlencode($selectedMonth); ?>')">
                                    <i class="fa-solid fa-trash-can"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="deleteModalLabel"><i class="fa-solid fa-triangle-exclamation me-2"></i> Confirm Advance Record Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="text-danger mb-3">
                    <i class="fa-solid fa-trash-can fa-3x"></i>
                </div>
                <h6 class="fw-bold mb-2">Are you sure you want to delete this advance record?</h6>
                <p class="text-muted small mb-3">
                    Staff Member: <strong id="modal_staff_name" class="text-dark"></strong><br>
                    Advance Amount: <strong id="modal_amount" class="text-danger fs-6"></strong>
                </p>
                <div class="alert alert-warning py-2 text-xs mb-0">
                    <i class="fa-solid fa-circle-info me-1"></i> Deleting this record will remove the deduction from the staff member's monthly paysheet.
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-center">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                <a id="modal_confirm_delete_btn" href="#" class="btn btn-danger font-weight-bold px-4">
                    <i class="fa-solid fa-trash-can me-1"></i> Yes, Delete Record
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function triggerDeleteModal(id, staffName, amount, month) {
    document.getElementById('modal_staff_name').innerText = staffName;
    document.getElementById('modal_amount').innerText = 'Rs. ' + amount;
    document.getElementById('modal_confirm_delete_btn').href = '<?php echo BASE_URL; ?>modules/staff/advances.php?action=delete&id=' + id + '&month=' + month;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
