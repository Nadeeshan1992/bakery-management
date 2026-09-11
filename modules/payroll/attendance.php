<?php
// modules/payroll/attendance.php - Upload Attendance (.xlsx / .csv) & Log Viewer
require_once __DIR__ . '/../../includes/header.php';

requireRole(['admin', 'owner']);

$db = getDB();
$message = '';
$error = '';
$importedCount = 0;

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['attendance_file'])) {
    $file = $_FILES['attendance_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload failed. Please try again.";
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt', 'xlsx'])) {
            $error = "Invalid file type. Please upload a .csv or .xlsx file.";
        } else {
            $filePath = $file['tmp_name'];
            $rows = [];

            if ($ext === 'csv' || $ext === 'txt') {
                if (($handle = fopen($filePath, 'r')) !== FALSE) {
                    $header = fgetcsv($handle, 1000, ","); // Skip header
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($data) >= 3 && !empty(trim($data[0]))) {
                            $rows[] = [
                                'emp_number' => trim($data[0]),
                                'date'       => trim($data[1]),
                                'status'     => strtolower(trim($data[2])),
                                'ot_hours'   => (float)($data[3] ?? 0),
                                'notes'      => trim($data[4] ?? '')
                            ];
                        }
                    }
                    fclose($handle);
                }
            } elseif ($ext === 'xlsx') {
                // Read XLSX using ZipArchive & SimpleXML
                $zip = new ZipArchive();
                if ($zip->open($filePath) === TRUE) {
                    $strings = [];
                    if (($styleIndex = $zip->locateName('xl/sharedStrings.xml')) !== FALSE) {
                        $xml = simplexml_load_string($zip->getFromIndex($styleIndex));
                        foreach ($xml->si as $val) {
                            $strings[] = (string)$val->t;
                        }
                    }

                    if (($sheetIndex = $zip->locateName('xl/worksheets/sheet1.xml')) !== FALSE) {
                        $xml = simplexml_load_string($zip->getFromIndex($sheetIndex));
                        $isHeader = true;
                        foreach ($xml->sheetData->row as $r) {
                            if ($isHeader) { $isHeader = false; continue; } // Skip header row
                            $cols = [];
                            foreach ($r->c as $c) {
                                $val = (string)$c->v;
                                if (isset($c['t']) && $c['t'] == 's') {
                                    $val = $strings[(int)$val] ?? $val;
                                }
                                $cols[] = $val;
                            }
                            if (count($cols) >= 3 && !empty(trim($cols[0]))) {
                                $rows[] = [
                                    'emp_number' => trim($cols[0]),
                                    'date'       => trim($cols[1]),
                                    'status'     => strtolower(trim($cols[2])),
                                    'ot_hours'   => (float)($cols[3] ?? 0),
                                    'notes'      => trim($cols[4] ?? '')
                                ];
                            }
                        }
                    }
                    $zip->close();
                } else {
                    $error = "Could not open Excel file.";
                }
            }

            // Process & Import Rows
            if (!empty($rows)) {
                $stmtStaff = $db->prepare("SELECT id FROM staff WHERE emp_number = ?");
                $stmtUpsert = $db->prepare("
                    INSERT INTO staff_attendance (staff_id, attendance_date, status, ot_hours, notes)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = VALUES(status), ot_hours = VALUES(ot_hours), notes = VALUES(notes)
                ");

                foreach ($rows as $row) {
                    $stmtStaff->execute([$row['emp_number']]);
                    $staffId = $stmtStaff->fetchColumn();

                    if ($staffId) {
                        // Normalize status
                        $statusVal = 'present';
                        if (in_array($row['status'], ['absent', 'half_day', 'half day', 'leave'])) {
                            $statusVal = str_replace(' ', '_', $row['status']);
                        }
                        
                        // Normalize date format
                        $dateVal = date('Y-m-d', strtotime($row['date']));

                        $stmtUpsert->execute([
                            $staffId, $dateVal, $statusVal, $row['ot_hours'], $row['notes']
                        ]);
                        $importedCount++;
                    }
                }

                $message = "Successfully processed and imported $importedCount attendance record(s)!";
            } else {
                if (empty($error)) $error = "No valid attendance records found in the uploaded file.";
            }
        }
    }
}

// Fetch Attendance Logs for viewing
$selectedMonth = $_GET['month'] ?? date('Y-m');
$stmtLogs = $db->prepare("
    SELECT a.*, s.emp_number, s.name as staff_name, s.designation 
    FROM staff_attendance a 
    JOIN staff s ON a.staff_id = s.id 
    WHERE DATE_FORMAT(a.attendance_date, '%Y-%m') = ? 
    ORDER BY a.attendance_date DESC, s.emp_number ASC
");
$stmtLogs->execute([$selectedMonth]);
$logs = $stmtLogs->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-excel text-success me-2"></i> Upload Attendance (.xlsx / .csv)</h4>
        <p class="text-muted small mb-0">Import staff daily attendance spreadsheets and manage overtime hours.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>modules/payroll/download_template.php" class="btn btn-outline-success">
            <i class="fa-solid fa-download me-1"></i> Download Sample Excel Template
        </a>
        <a href="<?php echo BASE_URL; ?>modules/payroll/index.php" class="btn btn-warning text-dark font-weight-bold">
            <i class="fa-solid fa-calculator me-1"></i> Go to Salary Payroll
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success d-flex align-items-center mb-4">
        <i class="fa-solid fa-circle-check me-2 fs-5"></i>
        <div><?php echo htmlspecialchars($message); ?></div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4">
        <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<!-- Upload Box -->
<div class="card card-bakery p-4 mb-4">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-cloud-arrow-up text-warning me-2"></i> Attendance File Upload</h5>
    <form method="POST" action="" enctype="multipart/form-data" class="row g-3 align-items-end">
        <div class="col-md-7">
            <label for="attendance_file" class="form-label fw-bold">Select Excel (.xlsx) or CSV File</label>
            <input type="file" class="form-control form-control-lg" id="attendance_file" name="attendance_file" accept=".csv, .xlsx, .txt" required>
            <div class="form-text">File should include columns: <code>emp_number</code>, <code>date (YYYY-MM-DD)</code>, <code>status (present/absent/half_day/leave)</code>, <code>ot_hours</code>.</div>
        </div>
        <div class="col-md-5">
            <button type="submit" class="btn btn-warning text-dark font-weight-bold btn-lg w-100">
                <i class="fa-solid fa-upload me-1"></i> Upload & Process Attendance
            </button>
        </div>
    </form>
</div>

<!-- Attendance Logs Section -->
<div class="card card-bakery p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-calendar-days text-primary me-2"></i> Attendance Log History</h5>
        <form method="GET" action="" class="d-flex align-items-center gap-2">
            <label for="month" class="form-label mb-0 font-weight-bold text-nowrap">Select Month:</label>
            <input type="month" id="month" name="month" class="form-control form-control-sm" value="<?php echo htmlspecialchars($selectedMonth); ?>" onchange="this.form.submit()">
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>EMP Code</th>
                    <th>Staff Name</th>
                    <th>Designation</th>
                    <th>Status</th>
                    <th>OT Hours</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No attendance records found for <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?>. Upload an Excel file above.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><strong><?php echo date('M d, Y', strtotime($log['attendance_date'])); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($log['emp_number']); ?></code></td>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($log['staff_name']); ?></strong></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($log['designation']); ?></span></td>
                            <td>
                                <?php if ($log['status'] === 'present'): ?>
                                    <span class="badge bg-success">Present</span>
                                <?php elseif ($log['status'] === 'half_day'): ?>
                                    <span class="badge bg-warning text-dark">Half Day</span>
                                <?php elseif ($log['status'] === 'leave'): ?>
                                    <span class="badge bg-info text-dark">Leave</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Absent</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo number_format($log['ot_hours'], 1); ?> hrs</strong></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($log['notes'] ?: '-'); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
