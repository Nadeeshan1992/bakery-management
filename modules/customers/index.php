<?php
// modules/customers/index.php - Customer Directory & Profiles
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $title = trim($_POST['title'] ?? 'Mr.');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $nic = trim($_POST['nic'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $creditLimit = floatval($_POST['credit_limit'] ?? 0);
    $creditPeriod = trim($_POST['credit_period'] ?? '30 Days');
    $openingBalance = floatval($_POST['opening_balance'] ?? 0);
    $branch = trim($_POST['branch'] ?? 'Main Branch');
    $vatEnabled = isset($_POST['vat_enabled']) ? 1 : 0;
    $vatNumber = trim($_POST['vat_number'] ?? '');
    $discountBiscuits = floatval($_POST['discount_biscuits'] ?? 0);
    $discountOther = floatval($_POST['discount_other'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!empty($name) && !empty($phone)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO customers 
                (title, name, phone, nic, email, address, credit_limit, credit_period, opening_balance, branch, vat_enabled, vat_number, special_discount, discount_biscuits, discount_other, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title, $name, $phone, $nic, $email, $address, 
                $creditLimit, $creditPeriod, $openingBalance, $branch, 
                $vatEnabled, $vatNumber, $discountBiscuits, $discountBiscuits, $discountOther, $notes
            ]);
            setFlash('success', 'Customer "' . htmlspecialchars($title . ' ' . $name) . '" registered successfully!');
            header('Location: ' . BASE_URL . 'modules/customers/index.php');
            exit;
        } catch (Exception $e) {
            setFlash('error', 'Error adding customer: ' . $e->getMessage());
        }
    }
}

$customers = $db->query("
    SELECT c.*, COUNT(o.id) as total_orders, COALESCE(SUM(o.total_amount), 0) as total_spent 
    FROM customers c 
    LEFT JOIN orders o ON c.id = o.customer_id 
    GROUP BY c.id 
    ORDER BY c.id DESC
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Customer Directory</h4>
        <p class="text-muted mb-0">Manage customer accounts, category discount rates, credit limits, and purchase history</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-success fw-bold" data-bs-toggle="modal" data-bs-target="#importExcelModal">
            <i class="fa-solid fa-file-excel me-1"></i> Import Excel (.xlsx)
        </button>
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
            <i class="fa-solid fa-plus me-1"></i> Quick Add Modal
        </button>
        <a href="<?php echo BASE_URL; ?>modules/customers/add.php" class="btn btn-warning text-dark fw-bold">
            <i class="fa-solid fa-user-plus me-1"></i> Register New Customer
        </a>
    </div>
</div>

<div class="card card-bakery p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Customer Name</th>
                    <th>Mobile Number</th>
                    <th>NIC</th>
                    <th>Branch</th>
                    <th>Credit Limit</th>
                    <th>Biscuits Disc %</th>
                    <th>Other Disc %</th>
                    <th class="text-center">Total Orders</th>
                    <th>Total Spend</th>
                    <th class="text-center" style="width: 105px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No customers registered yet. Click 'Register New Customer' to add one.</td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars(($c['title'] ?? '') . ' ' . $c['name']); ?></strong>
                                <?php if ($c['vat_enabled']): ?>
                                    <span class="badge bg-info text-dark ms-1">VAT: <?php echo htmlspecialchars($c['vat_number']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><i class="fa-solid fa-phone me-1 text-muted"></i><?php echo htmlspecialchars($c['phone'] ?? 'N/A'); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($c['nic'] ?? 'N/A'); ?></span></td>
                            <td><span class="text-muted text-xs"><?php echo htmlspecialchars($c['branch'] ?? 'Main Branch'); ?></span></td>
                            <td><strong class="text-primary"><?php echo formatMoney($c['credit_limit'] ?? 0); ?></strong></td>
                            <td><span class="badge bg-warning text-dark"><?php echo number_format($c['discount_biscuits'] ?? $c['special_discount'] ?? 0, 2); ?>%</span></td>
                            <td><span class="badge bg-success"><?php echo number_format($c['discount_other'] ?? $c['special_discount'] ?? 0, 2); ?>%</span></td>
                            <td class="text-center"><span class="badge bg-secondary"><?php echo $c['total_orders']; ?></span></td>
                            <td><strong class="text-success"><?php echo formatMoney($c['total_spent']); ?></strong></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?php echo BASE_URL; ?>modules/customers/edit.php?id=<?php echo $c['id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Edit Customer">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <?php if ($c['id'] != 1): ?>
                                        <a href="<?php echo BASE_URL; ?>modules/customers/delete.php?id=<?php echo $c['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete customer \'<?php echo addslashes(htmlspecialchars(($c['title'] ?? '') . ' ' . $c['name'])); ?>\'?');" data-bs-toggle="tooltip" title="Delete Customer">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-secondary disabled" title="Default Walk-in customer cannot be deleted" disabled>
                                            <i class="fa-solid fa-lock"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Quick Add Customer -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus text-warning me-2"></i> Quick Register Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label font-weight-bold">Title</label>
                            <select name="title" class="form-select">
                                <option value="Mr.">Mr.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Miss">Miss</option>
                                <option value="Dr.">Dr.</option>
                                <option value="Rev.">Rev.</option>
                                <option value="M/S">M/S</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label font-weight-bold">* Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Customer Name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-weight-bold">* Mobile Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 0771234567" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Address</label>
                            <input type="text" name="address" class="form-control" placeholder="Address">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-weight-bold">Credit Limit (Rs.)</label>
                            <input type="number" step="0.01" min="0" name="credit_limit" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-weight-bold">NIC Number</label>
                            <input type="text" name="nic" class="form-control" placeholder="NIC Number">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-weight-bold text-primary">Special Discount % (for Biscuits)</label>
                            <input type="number" step="0.01" min="0" max="100" name="discount_biscuits" class="form-control" placeholder="0.00%">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold text-success">Special Discount % (for Other)</label>
                            <input type="number" step="0.01" min="0" max="100" name="discount_other" class="form-control" placeholder="0.00%">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_customer" value="1" class="btn btn-primary fw-bold">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Import Excel (.xlsx / .csv) -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fa-solid fa-file-excel text-success me-2"></i> Import Customers from Excel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo BASE_URL; ?>modules/customers/import.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Download Template Box -->
                    <div class="p-3 mb-3 bg-light rounded border d-flex align-items-center justify-content-between">
                        <div>
                            <strong class="d-block text-dark small">Sample Excel Template:</strong>
                            <small class="text-muted">Use this format to prepare your list</small>
                        </div>
                        <a href="<?php echo BASE_URL; ?>assets/templates/customer_import_template.xlsx" download class="btn btn-sm btn-outline-success text-nowrap fw-semibold">
                            <i class="fa-solid fa-download me-1"></i> Sample .xlsx
                        </a>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold text-dark">
                            <span class="text-danger">*</span> Select .xlsx or .csv File:
                        </label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.csv" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label font-weight-bold small text-muted text-uppercase mb-1">Duplicate Phone Handling:</label>
                        <div class="form-check small mb-1">
                            <input class="form-check-input" type="radio" name="duplicate_action" id="modalDupSkip" value="skip" checked>
                            <label class="form-check-label" for="modalDupSkip">
                                <strong>Skip existing</strong> phone numbers
                            </label>
                        </div>
                        <div class="form-check small">
                            <input class="form-check-input" type="radio" name="duplicate_action" id="modalDupUpdate" value="update">
                            <label class="form-check-label" for="modalDupUpdate">
                                <strong>Update existing</strong> customer details
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>modules/customers/import.php" class="small text-decoration-none">
                        <i class="fa-solid fa-up-right-from-square me-1"></i> Advanced Import Page
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm fw-bold">
                            <i class="fa-solid fa-upload me-1"></i> Upload & Import
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
