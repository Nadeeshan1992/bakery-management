<?php
// modules/customers/import.php - Bulk Import Customers from Excel (.xlsx) or CSV
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/xlsx_helper.php';

requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $duplicateAction = $_POST['duplicate_action'] ?? 'skip'; // 'skip' or 'update'

    if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        setFlash('error', 'File upload failed. Please choose a valid .xlsx or .csv file.');
        header('Location: ' . BASE_URL . 'modules/customers/import.php');
        exit;
    }

    $fileName = $_FILES['excel_file']['name'];
    $tmpPath = $_FILES['excel_file']['tmp_name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx', 'csv'])) {
        setFlash('error', 'Invalid file format. Only Excel (.xlsx) and CSV (.csv) files are supported.');
        header('Location: ' . BASE_URL . 'modules/customers/import.php');
        exit;
    }

    try {
        $rows = SimpleXLSXHelper::parse($tmpPath);

        if (empty($rows)) {
            setFlash('error', 'The uploaded file contains no data or could not be read.');
            header('Location: ' . BASE_URL . 'modules/customers/import.php');
            exit;
        }

        // Detect header row
        $headerRow = array_shift($rows);
        $colMap = [];

        foreach ($headerRow as $idx => $colName) {
            $cleaned = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string)$colName)));
            if (in_array($cleaned, ['title', 'prefix'])) $colMap['title'] = $idx;
            elseif (in_array($cleaned, ['name', 'customername', 'fullname'])) $colMap['name'] = $idx;
            elseif (in_array($cleaned, ['phone', 'mobile', 'mobilenumber', 'contact', 'telephone', 'phonenumber'])) $colMap['phone'] = $idx;
            elseif (in_array($cleaned, ['address', 'location', 'street'])) $colMap['address'] = $idx;
            elseif (in_array($cleaned, ['nic', 'nicnumber', 'idnumber', 'nationalid'])) $colMap['nic'] = $idx;
            elseif (in_array($cleaned, ['creditlimit', 'limit', 'credit'])) $colMap['credit_limit'] = $idx;
            elseif (in_array($cleaned, ['creditperiod', 'period', 'terms'])) $colMap['credit_period'] = $idx;
            elseif (in_array($cleaned, ['openingbalance', 'balance', 'startingbalance'])) $colMap['opening_balance'] = $idx;
            elseif (in_array($cleaned, ['branch', 'outlet', 'store'])) $colMap['branch'] = $idx;
            elseif (in_array($cleaned, ['vatenabled', 'vat', 'taxenabled'])) $colMap['vat_enabled'] = $idx;
            elseif (in_array($cleaned, ['vatnumber', 'vatno', 'taxnumber'])) $colMap['vat_number'] = $idx;
            elseif (in_array($cleaned, ['biscuitsdiscount', 'discountbiscuits', 'biscuitsdisc', 'biscuitsdiscountpercentage'])) $colMap['discount_biscuits'] = $idx;
            elseif (in_array($cleaned, ['otherdiscount', 'discountother', 'otherdisc', 'otherdiscountpercentage'])) $colMap['discount_other'] = $idx;
            elseif (in_array($cleaned, ['email', 'emailaddress'])) $colMap['email'] = $idx;
            elseif (in_array($cleaned, ['notes', 'remarks', 'comments', 'preferences'])) $colMap['notes'] = $idx;
        }

        // Fallback default index mapping if headers don't match exact text
        if (!isset($colMap['name']) && count($headerRow) >= 2) {
            // Assume Col 0: Title, Col 1: Name, Col 2: Phone
            $colMap['title'] = 0;
            $colMap['name'] = 1;
            $colMap['phone'] = 2;
            $colMap['address'] = 3;
            $colMap['nic'] = 4;
            $colMap['credit_limit'] = 5;
            $colMap['credit_period'] = 6;
            $colMap['opening_balance'] = 7;
            $colMap['branch'] = 8;
            $colMap['vat_enabled'] = 9;
            $colMap['vat_number'] = 10;
            $colMap['discount_biscuits'] = 11;
            $colMap['discount_other'] = 12;
            $colMap['email'] = 13;
            $colMap['notes'] = 14;
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $stmtCheckPhone = $db->prepare("SELECT id FROM customers WHERE phone = ?");
        $stmtInsert = $db->prepare("
            INSERT INTO customers 
            (title, profile_picture, name, phone, nic, email, address, credit_limit, credit_period, opening_balance, branch, vat_enabled, vat_number, special_discount, discount_biscuits, discount_other, notes) 
            VALUES (?, 'default_avatar.png', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtUpdate = $db->prepare("
            UPDATE customers SET 
                title = ?, name = ?, nic = ?, email = ?, address = ?, 
                credit_limit = ?, credit_period = ?, opening_balance = ?, branch = ?, 
                vat_enabled = ?, vat_number = ?, special_discount = ?, 
                discount_biscuits = ?, discount_other = ?, notes = ? 
            WHERE id = ?
        ");

        foreach ($rows as $rowIndex => $row) {
            $lineNum = $rowIndex + 2; // account for 1-based index and header

            $name = isset($colMap['name']) ? trim($row[$colMap['name']] ?? '') : '';
            $phone = isset($colMap['phone']) ? trim($row[$colMap['phone']] ?? '') : '';

            // Clean phone number (remove spaces and dashes)
            $cleanPhone = preg_replace('/[\s\-]/', '', $phone);

            if (empty($name) || empty($cleanPhone)) {
                $skipped++;
                continue;
            }

            $title = isset($colMap['title']) && !empty($row[$colMap['title']]) ? trim($row[$colMap['title']]) : 'Mr.';
            if (!in_array($title, ['Mr.', 'Mrs.', 'Miss', 'Dr.', 'Rev.', 'M/S'])) {
                $title = 'Mr.';
            }

            $address = isset($colMap['address']) ? trim($row[$colMap['address']] ?? '') : '';
            $nic = isset($colMap['nic']) ? trim($row[$colMap['nic']] ?? '') : '';
            $creditLimit = isset($colMap['credit_limit']) ? floatval($row[$colMap['credit_limit']] ?? 0) : 0.00;
            $creditPeriod = isset($colMap['credit_period']) && !empty($row[$colMap['credit_period']]) ? trim($row[$colMap['credit_period']]) : '30 Days';
            $openingBalance = isset($colMap['opening_balance']) ? floatval($row[$colMap['opening_balance']] ?? 0) : 0.00;
            $branch = isset($colMap['branch']) && !empty($row[$colMap['branch']]) ? trim($row[$colMap['branch']]) : 'Main Branch';
            
            $vatRaw = isset($colMap['vat_enabled']) ? strtolower(trim($row[$colMap['vat_enabled']] ?? '')) : '';
            $vatEnabled = in_array($vatRaw, ['1', 'yes', 'true', 'y']) ? 1 : 0;
            $vatNumber = isset($colMap['vat_number']) ? trim($row[$colMap['vat_number']] ?? '') : '';

            $discountBiscuits = isset($colMap['discount_biscuits']) ? floatval($row[$colMap['discount_biscuits']] ?? 0) : 0.00;
            $discountOther = isset($colMap['discount_other']) ? floatval($row[$colMap['discount_other']] ?? 0) : 0.00;
            $email = isset($colMap['email']) ? trim($row[$colMap['email']] ?? '') : '';
            $notes = isset($colMap['notes']) ? trim($row[$colMap['notes']] ?? '') : '';

            // Check duplicate phone
            $stmtCheckPhone->execute([$cleanPhone]);
            $existing = $stmtCheckPhone->fetch();

            if ($existing) {
                if ($duplicateAction === 'update') {
                    $stmtUpdate->execute([
                        $title, $name, $nic, $email, $address,
                        $creditLimit, $creditPeriod, $openingBalance, $branch,
                        $vatEnabled, $vatNumber, $discountBiscuits,
                        $discountBiscuits, $discountOther, $notes,
                        $existing['id']
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $stmtInsert->execute([
                    $title, $name, $cleanPhone, $nic, $email, $address,
                    $creditLimit, $creditPeriod, $openingBalance, $branch,
                    $vatEnabled, $vatNumber, $discountBiscuits,
                    $discountBiscuits, $discountOther, $notes
                ]);
                $inserted++;
            }
        }

        $msg = "Import completed successfully: {$inserted} new customer(s) added";
        if ($updated > 0) $msg .= ", {$updated} customer(s) updated";
        if ($skipped > 0) $msg .= ", {$skipped} skipped (duplicates/empty)";
        $msg .= ".";

        setFlash('success', $msg);
        header('Location: ' . BASE_URL . 'modules/customers/index.php');
        exit;
    } catch (Exception $e) {
        setFlash('error', 'Error processing spreadsheet: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'modules/customers/import.php');
        exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-bakery p-4 shadow-sm border-0" style="border-radius: 16px;">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h4 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-file-excel text-success me-2"></i> Import Customers from Excel (.xlsx)
                    </h4>
                    <small class="text-muted">Upload an Excel spreadsheet or CSV file to bulk add customer accounts</small>
                </div>
                <a href="<?php echo BASE_URL; ?>modules/customers/index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Directory
                </a>
            </div>

            <!-- Download Template Banner -->
            <div class="alert alert-light border d-flex align-items-center justify-content-between p-3 mb-4" style="border-radius: 12px; background-color: #f8fafc;">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-file-arrow-down text-success fa-2x me-3"></i>
                    <div>
                        <strong class="d-block text-dark">Need a pre-formatted Excel template?</strong>
                        <small class="text-muted">Download our ready-to-use template with headers and sample customer rows.</small>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>assets/templates/customer_import_template.xlsx" download="customer_import_template.xlsx" class="btn btn-success btn-sm fw-bold text-nowrap">
                    <i class="fa-solid fa-download me-1"></i> Download .XLSX Template
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label font-weight-bold text-dark">
                        <span class="text-danger">*</span> Choose Excel (.xlsx) or CSV File:
                    </label>
                    <input type="file" name="excel_file" class="form-control form-control-lg" accept=".xlsx,.csv" required>
                    <small class="text-muted mt-1 d-block">Supported formats: Microsoft Excel Spreadsheet (<strong>.xlsx</strong>), Comma Separated Values (<strong>.csv</strong>)</small>
                </div>

                <div class="mb-4 p-3 bg-light rounded border">
                    <label class="form-label font-weight-bold text-dark mb-2">Duplicate Phone Number Handling:</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="duplicate_action" id="dupSkip" value="skip" checked>
                        <label class="form-check-label" for="dupSkip">
                            <strong>Skip duplicates (Recommended)</strong> — If a customer mobile number already exists, leave current record unchanged.
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="duplicate_action" id="dupUpdate" value="update">
                        <label class="form-check-label" for="dupUpdate">
                            <strong>Update existing customers</strong> — If mobile number exists, overwrite with the new details from the spreadsheet.
                        </label>
                    </div>
                </div>

                <!-- Column Reference Guide -->
                <div class="mb-4">
                    <a class="text-decoration-none small fw-bold" data-bs-toggle="collapse" href="#columnsGuide" role="button">
                        <i class="fa-solid fa-circle-info me-1"></i> View Supported Spreadsheet Columns & Sample Format
                    </a>
                    <div class="collapse mt-2" id="columnsGuide">
                        <div class="card card-body bg-light border-0 small">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Column Header</th>
                                        <th>Required?</th>
                                        <th>Description / Example</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>Title</code></td><td>No</td><td>e.g. Mr., Mrs., Miss, Dr., Rev., M/S (Default: Mr.)</td></tr>
                                    <tr><td><code>Name</code></td><td><strong>YES</strong></td><td>Customer Full Name (e.g. Kamal Perera)</td></tr>
                                    <tr><td><code>Phone</code></td><td><strong>YES</strong></td><td>Mobile number (e.g. 0771234567) - used as unique key</td></tr>
                                    <tr><td><code>Address</code></td><td>No</td><td>Street or city address</td></tr>
                                    <tr><td><code>NIC</code></td><td>No</td><td>National Identity Card (e.g. 198512345678 / 851234567V)</td></tr>
                                    <tr><td><code>Credit Limit</code></td><td>No</td><td>Max credit allowance in Rs. (e.g. 50000)</td></tr>
                                    <tr><td><code>Credit Period</code></td><td>No</td><td>e.g. 30 Days, 15 Days</td></tr>
                                    <tr><td><code>Opening Balance</code></td><td>No</td><td>Starting balance amount in Rs.</td></tr>
                                    <tr><td><code>Branch</code></td><td>No</td><td>e.g. Main Branch</td></tr>
                                    <tr><td><code>VAT Enabled</code></td><td>No</td><td>Yes / No or 1 / 0</td></tr>
                                    <tr><td><code>VAT Number</code></td><td>No</td><td>VAT registration number</td></tr>
                                    <tr><td><code>Biscuits Discount %</code></td><td>No</td><td>Percentage discount for biscuits (e.g. 10.0)</td></tr>
                                    <tr><td><code>Other Discount %</code></td><td>No</td><td>Percentage discount for other bakery items (e.g. 5.0)</td></tr>
                                    <tr><td><code>Email</code></td><td>No</td><td>Email address</td></tr>
                                    <tr><td><code>Notes</code></td><td>No</td><td>Customer preferences, dietary requirements, etc.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="<?php echo BASE_URL; ?>modules/customers/index.php" class="btn btn-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-success px-5 fw-bold">
                        <i class="fa-solid fa-file-import me-1"></i> Upload & Import Customers
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
