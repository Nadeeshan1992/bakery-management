<?php
// modules/staff/food_bill.php - Staff Food Items Consumption Bill / Voucher Printout
require_once __DIR__ . '/../../includes/header.php';

requireRole(['admin', 'owner', 'sales_person']);

$db = getDB();

$billNo = trim($_GET['bill_no'] ?? '');
$entryId = (int)($_GET['id'] ?? 0);

if (!$billNo && $entryId > 0) {
    // Lookup bill_no from entry id
    $stmtFind = $db->prepare("SELECT bill_no FROM staff_food_consumption WHERE id = ?");
    $stmtFind->execute([$entryId]);
    $found = $stmtFind->fetch();
    if ($found && !empty($found['bill_no'])) {
        $billNo = $found['bill_no'];
    }
}

// Fetch items for this bill_no or entryId
if ($billNo) {
    $stmt = $db->prepare("
        SELECT f.*, s.emp_number, s.name as staff_name, s.designation, s.phone as staff_phone, s.nic as staff_nic, u.full_name as issuer_name 
        FROM staff_food_consumption f 
        JOIN staff s ON f.staff_id = s.id 
        LEFT JOIN users u ON f.created_by = u.id 
        WHERE f.bill_no = ? 
        ORDER BY f.id ASC
    ");
    $stmt->execute([$billNo]);
    $items = $stmt->fetchAll();
} elseif ($entryId > 0) {
    $stmt = $db->prepare("
        SELECT f.*, s.emp_number, s.name as staff_name, s.designation, s.phone as staff_phone, s.nic as staff_nic, u.full_name as issuer_name 
        FROM staff_food_consumption f 
        JOIN staff s ON f.staff_id = s.id 
        LEFT JOIN users u ON f.created_by = u.id 
        WHERE f.id = ?
    ");
    $stmt->execute([$entryId]);
    $items = $stmt->fetchAll();
} else {
    $items = [];
}

if (empty($items)) {
    setFlash('danger', 'Food consumption bill record not found.');
    header('Location: ' . BASE_URL . 'modules/staff/food_consumption.php');
    exit;
}

$first = $items[0];
$displayBillNo = $first['bill_no'] ?: ('SFC-' . str_pad($first['id'], 5, '0', STR_PAD_LEFT));
$staffName = $first['staff_name'];
$empNumber = $first['emp_number'];
$designation = $first['designation'];
$staffPhone = $first['staff_phone'];
$issuerName = $first['issuer_name'] ?: 'Staff / Manager';
$consumptionDate = $first['consumption_date'];
$createdAt = $first['created_at'];
$consumptionMonth = date('F Y', strtotime($consumptionDate));

// Calculate totals
$totalQty = 0;
$grandTotal = 0;
foreach ($items as $item) {
    $totalQty += (float)$item['quantity'];
    $grandTotal += (float)$item['total_price'];
}
?>

<style>
@media print {
    body {
        background: #fff !important;
        color: #000 !important;
    }
    .no-print, header, nav, .navbar, .sidebar, footer, .main-footer {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    #printableBill {
        border: 1px solid #000 !important;
        box-shadow: none !important;
        margin: 0 auto !important;
        max-width: 100% !important;
    }
}
.bill-receipt {
    max-width: 580px;
    margin: 0 auto;
    background: #fff;
    border-radius: 12px;
}
.dashed-line {
    border-top: 2px dashed #cbd5e1;
    margin: 14px 0;
}
</style>

<div class="container py-3">
    <!-- Top Actions (No Print) -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print" style="max-width: 580px; margin: 0 auto;">
        <a href="<?php echo BASE_URL; ?>modules/staff/food_consumption.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Consumption
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-warning text-dark fw-bold px-4 shadow-sm">
                <i class="fa-solid fa-print me-2"></i> Print Bill
            </button>
        </div>
    </div>

    <!-- Printable Bill Card -->
    <div id="printableBill" class="card card-bakery p-4 bill-receipt shadow-sm border">
        <!-- Bakery Header -->
        <div class="text-center pb-2">
            <img src="<?php echo BASE_URL; ?>assets/images/mlb_logo.jpg" alt="MLB Logo" class="mb-2" style="height: 65px; border-radius: 8px;">
            <h4 class="fw-bold mb-0 text-dark">MLB BAKERY & CONFECTIONERY</h4>
            <p class="text-muted mb-1 small">Staff Food Items Consumption Voucher</p>
            <span class="badge bg-dark text-warning border px-3 py-1 font-monospace" style="font-size: 0.85rem;">
                BILL #: <?php echo htmlspecialchars($displayBillNo); ?>
            </span>
        </div>

        <div class="dashed-line"></div>

        <!-- Meta Information -->
        <div class="row g-2 text-sm mb-2" style="font-size: 0.85rem;">
            <div class="col-6">
                <span class="text-muted d-block" style="font-size: 0.75rem;">STAFF MEMBER:</span>
                <strong class="text-dark fs-6"><?php echo htmlspecialchars($staffName); ?></strong>
                <div class="text-muted"><code><?php echo htmlspecialchars($empNumber); ?></code> &bull; <?php echo htmlspecialchars($designation); ?></div>
                <?php if ($staffPhone): ?>
                    <div class="text-muted small"><i class="fa-solid fa-phone me-1"></i> <?php echo htmlspecialchars($staffPhone); ?></div>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <span class="text-muted d-block" style="font-size: 0.75rem;">DATE & TIME:</span>
                <strong class="text-dark"><?php echo date('M d, Y', strtotime($consumptionDate)); ?></strong>
                <div class="text-muted small"><?php echo date('h:i A', strtotime($createdAt)); ?></div>
                <div class="text-muted small mt-1">Issued By: <strong><?php echo htmlspecialchars($issuerName); ?></strong></div>
            </div>
        </div>

        <div class="dashed-line"></div>

        <!-- Itemized Food Items Table -->
        <div class="table-responsive">
            <table class="table table-sm table-borderless mb-2" style="font-size: 0.85rem;">
                <thead>
                    <tr class="border-bottom text-muted" style="font-size: 0.75rem;">
                        <th style="width: 8%;">#</th>
                        <th style="width: 47%;">Bakery Item</th>
                        <th class="text-center" style="width: 15%;">Qty</th>
                        <th class="text-end" style="width: 15%;">Retail Price</th>
                        <th class="text-end" style="width: 15%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rowIdx = 1;
                    foreach ($items as $it): 
                    ?>
                        <tr class="border-bottom border-light">
                            <td class="text-muted"><?php echo $rowIdx++; ?></td>
                            <td>
                                <strong class="text-dark"><?php echo htmlspecialchars($it['product_name']); ?></strong>
                                <?php if (!empty($it['notes'])): ?>
                                    <small class="text-muted d-block" style="font-size: 0.7rem;"><em>Note: <?php echo htmlspecialchars($it['notes']); ?></em></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold"><?php echo number_format($it['quantity'], 1); ?></td>
                            <td class="text-end text-muted"><?php echo number_format($it['unit_price'], 2); ?></td>
                            <td class="text-end fw-bold text-dark"><?php echo number_format($it['total_price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals & Breakdown -->
        <div class="row justify-content-end text-end mt-2" style="font-size: 0.9rem;">
            <div class="col-7 text-muted">Total Food Items:</div>
            <div class="col-5 fw-bold text-dark"><?php echo count($items); ?> items (<?php echo number_format($totalQty, 1); ?> pcs)</div>

            <div class="col-7 fw-bold text-dark fs-5 pt-2 border-top">Grand Total Cost:</div>
            <div class="col-5 fw-bold text-danger fs-5 pt-2 border-top">Rs. <?php echo number_format($grandTotal, 2); ?></div>
        </div>

        <!-- Monthly Payroll Deduction Notice -->
        <div class="alert alert-light border mt-3 mb-3 p-2 rounded text-xs" style="font-size: 0.75rem; background: #fffcf8; border-color: #fed7aa !important;">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-calculator text-warning fs-6"></i>
                <div>
                    <strong>Monthly Payroll Deduction Notice:</strong><br>
                    This amount of <strong>Rs. <?php echo number_format($grandTotal, 2); ?></strong> is debited as staff meal consumption and will be automatically deducted from <strong><?php echo htmlspecialchars($staffName); ?></strong>'s salary for <strong><?php echo $consumptionMonth; ?></strong>.
                </div>
            </div>
        </div>

        <!-- Signatures Box -->
        <div class="row pt-4 mt-3 text-center" style="font-size: 0.78rem;">
            <div class="col-6">
                <div style="border-top: 1px dotted #000; width: 85%; margin: 0 auto; padding-top: 5px;">
                    <strong>Staff Member Signature</strong><br>
                    <small class="text-muted">(Received Items)</small>
                </div>
            </div>
            <div class="col-6">
                <div style="border-top: 1px dotted #000; width: 85%; margin: 0 auto; padding-top: 5px;">
                    <strong>Authorized By</strong><br>
                    <small class="text-muted">(Manager / Cashier)</small>
                </div>
            </div>
        </div>

        <!-- Bill Footer -->
        <div class="text-center mt-4 pt-2 border-top border-2 border-dashed text-muted" style="font-size: 0.72rem;">
            <p class="mb-0">MLB Bakery Management System &bull; Internal Issue Record</p>
            <p class="mb-0">Keep this copy for salary reconciliation &bull; Printed: <?php echo date('Y-m-d h:i A'); ?></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
