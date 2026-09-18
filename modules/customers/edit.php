<?php
// modules/customers/edit.php - Edit Customer Details
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlash('error', 'Invalid customer ID.');
    header('Location: ' . BASE_URL . 'modules/customers/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    header('Location: ' . BASE_URL . 'modules/customers/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? 'Mr.');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $nic = trim($_POST['nic'] ?? '');
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

    // Handle Profile Picture Upload
    $profilePicName = $customer['profile_picture'] ?? 'default_avatar.png';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../../assets/uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $profilePicName = $newFileName;
            }
        }
    }

    if (empty($name) || empty($phone)) {
        setFlash('error', 'Please provide both Customer Name and Mobile Number.');
    } else {
        try {
            $stmtUpdate = $db->prepare("
                UPDATE customers SET 
                    title = ?, profile_picture = ?, name = ?, phone = ?, nic = ?, 
                    email = ?, address = ?, credit_limit = ?, credit_period = ?, 
                    opening_balance = ?, branch = ?, vat_enabled = ?, vat_number = ?, 
                    special_discount = ?, discount_biscuits = ?, discount_other = ?, notes = ? 
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $title,
                $profilePicName,
                $name,
                $phone,
                $nic,
                $email,
                $address,
                $creditLimit,
                $creditPeriod,
                $openingBalance,
                $branch,
                $vatEnabled,
                $vatNumber,
                $discountBiscuits,
                $discountBiscuits,
                $discountOther,
                $notes,
                $id
            ]);

            setFlash('success', 'Customer "' . htmlspecialchars($title . ' ' . $name) . '" updated successfully!');
            header('Location: ' . BASE_URL . 'modules/customers/index.php');
            exit;
        } catch (Exception $e) {
            setFlash('error', 'Error updating customer: ' . $e->getMessage());
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card card-bakery p-4 shadow-sm border-0" style="border-radius: 16px;">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h4 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-user-pen text-warning me-2"></i> Edit Customer Details
                    </h4>
                    <small class="text-muted">Updating profile for: <strong><?php echo htmlspecialchars(($customer['title'] ?? '') . ' ' . $customer['name']); ?></strong> (ID: #<?php echo $customer['id']; ?>)</small>
                </div>
                <a href="<?php echo BASE_URL; ?>modules/customers/index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Directory
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Profile Picture Section -->
                <div class="mb-4">
                    <label class="form-label font-weight-bold text-secondary">Profile Picture</label>
                    <div class="d-flex align-items-center gap-3">
                        <div class="border rounded p-1 bg-light text-center" style="width: 90px; height: 90px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <?php 
                            $avatarPath = BASE_URL . 'assets/images/default_avatar.png';
                            if (!empty($customer['profile_picture']) && $customer['profile_picture'] !== 'default_avatar.png' && file_exists(__DIR__ . '/../../assets/uploads/' . $customer['profile_picture'])) {
                                $avatarPath = BASE_URL . 'assets/uploads/' . htmlspecialchars($customer['profile_picture']);
                            }
                            ?>
                            <img id="imagePreview" src="<?php echo $avatarPath; ?>" alt="Customer Avatar" style="max-width:100%; max-height:100%; object-fit:cover; border-radius:8px;">
                        </div>
                        <div>
                            <input type="file" name="profile_picture" id="profilePicInput" class="form-control form-control-sm" accept="image/*" onchange="previewFile()">
                            <small class="text-muted d-block mt-1">Upload JPEG, PNG or WebP image to replace current picture (Optional)</small>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Row 1: Title, Name, Mobile Number, Address -->
                    <div class="col-md-2">
                        <label class="form-label font-weight-bold">Title</label>
                        <select name="title" class="form-select">
                            <?php 
                            $titles = ['Mr.', 'Mrs.', 'Miss', 'Dr.', 'Rev.', 'M/S'];
                            foreach ($titles as $t): 
                            ?>
                                <option value="<?php echo $t; ?>" <?php echo (($customer['title'] ?? '') === $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold"><span class="text-danger">*</span> Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($customer['name']); ?>" placeholder="Customer Name" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold"><span class="text-danger">*</span> Mobile Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" placeholder="e.g. 0771234567" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Address</label>
                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" placeholder="Street Address">
                    </div>

                    <!-- Row 2: Credit Limit, Credit Period, NIC, Opening Balance -->
                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Credit Limit (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="credit_limit" class="form-control" value="<?php echo htmlspecialchars($customer['credit_limit'] ?? '0.00'); ?>" placeholder="0.00">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Credit Period</label>
                        <select name="credit_period" class="form-select">
                            <?php 
                            $periods = ['7 Days', '15 Days', '30 Days', '60 Days', '90 Days'];
                            $currentPeriod = $customer['credit_period'] ?? '30 Days';
                            foreach ($periods as $p): 
                            ?>
                                <option value="<?php echo $p; ?>" <?php echo ($currentPeriod === $p) ? 'selected' : ''; ?>><?php echo $p; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">NIC Number</label>
                        <input type="text" name="nic" class="form-control" value="<?php echo htmlspecialchars($customer['nic'] ?? ''); ?>" placeholder="e.g. 199012345678 / 901234567V">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Opening Balance (Rs.)</label>
                        <input type="number" step="0.01" name="opening_balance" class="form-control" value="<?php echo htmlspecialchars($customer['opening_balance'] ?? '0.00'); ?>" placeholder="0.00">
                    </div>

                    <!-- Row 3: Branch, Vat Enabled, Vat Number -->
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Branch</label>
                        <select name="branch" class="form-select">
                            <?php 
                            $branches = ['Main Branch', 'City Branch', 'Westside Branch'];
                            $currentBranch = $customer['branch'] ?? 'Main Branch';
                            foreach ($branches as $b): 
                            ?>
                                <option value="<?php echo $b; ?>" <?php echo ($currentBranch === $b) ? 'selected' : ''; ?>><?php echo $b; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-center pt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="vat_enabled" id="vatEnabled" onchange="toggleVatNumber()" <?php echo (!empty($customer['vat_enabled'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label font-weight-bold" for="vatEnabled">
                                Vat Enabled
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Vat Number</label>
                        <input type="text" name="vat_number" id="vatNumberInput" class="form-control" value="<?php echo htmlspecialchars($customer['vat_number'] ?? ''); ?>" placeholder="Vat Number" <?php echo empty($customer['vat_enabled']) ? 'disabled' : ''; ?>>
                    </div>

                    <!-- Row 4: Category Specific Discounts (Biscuits & Other) -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold text-primary"><i class="fa-solid fa-cookie me-1"></i> Special Discount % (for Biscuits)</label>
                        <input type="number" step="0.01" min="0" max="100" name="discount_biscuits" class="form-control border-primary" value="<?php echo htmlspecialchars($customer['discount_biscuits'] ?? $customer['special_discount'] ?? '0.00'); ?>" placeholder="0.00%">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-weight-bold text-success"><i class="fa-solid fa-layer-group me-1"></i> Special Discount % (for Other items)</label>
                        <input type="number" step="0.01" min="0" max="100" name="discount_other" class="form-control border-success" value="<?php echo htmlspecialchars($customer['discount_other'] ?? $customer['special_discount'] ?? '0.00'); ?>" placeholder="0.00%">
                    </div>

                    <!-- Row 5: Email -->
                    <div class="col-md-12">
                        <label class="form-label font-weight-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" placeholder="customer@example.com">
                    </div>

                    <div class="col-12">
                        <label class="form-label font-weight-bold">Additional Notes / Preferences</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Birthday, Anniversary, Eggless/Nut Allergy preference"><?php echo htmlspecialchars($customer['notes'] ?? ''); ?></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 text-end mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="<?php echo BASE_URL; ?>modules/customers/index.php" class="btn btn-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Update Customer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleVatNumber() {
    const chk = document.getElementById('vatEnabled');
    const txt = document.getElementById('vatNumberInput');
    txt.disabled = !chk.checked;
    if (!chk.checked) txt.value = '';
}

function previewFile() {
    const preview = document.getElementById('imagePreview');
    const file = document.getElementById('profilePicInput').files[0];
    const reader = new FileReader();

    reader.addEventListener("load", function () {
        preview.src = reader.result;
        preview.style.display = "block";
    }, false);

    if (file) {
        reader.readAsDataURL(file);
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
