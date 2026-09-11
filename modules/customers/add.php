<?php
// modules/customers/add.php - New Customer Registration
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$db = getDB();

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
    $profilePicName = 'default_avatar.png';
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
            $stmt = $db->prepare("
                INSERT INTO customers 
                (title, profile_picture, name, phone, nic, email, address, credit_limit, credit_period, opening_balance, branch, vat_enabled, vat_number, special_discount, discount_biscuits, discount_other, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
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
                $discountBiscuits, // fallback for legacy special_discount field
                $discountBiscuits,
                $discountOther,
                $notes
            ]);

            setFlash('success', 'Customer "' . htmlspecialchars($title . ' ' . $name) . '" registered successfully!');
            header('Location: ' . BASE_URL . 'modules/customers/index.php');
            exit;
        } catch (Exception $e) {
            setFlash('error', 'Error registering customer: ' . $e->getMessage());
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card card-bakery p-4 shadow-sm border-0" style="border-radius: 16px;">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <h4 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-user-plus text-warning me-2"></i> New Customer Registration
                </h4>
                <a href="<?php echo BASE_URL; ?>modules/customers/index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-xmark me-1"></i> Cancel
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Profile Picture Section -->
                <div class="mb-4">
                    <label class="form-label font-weight-bold text-secondary">Profile Picture</label>
                    <div class="d-flex align-items-center gap-3">
                        <div class="border rounded p-2 bg-light text-center" style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-camera fa-2x text-muted" id="previewIcon"></i>
                            <img id="imagePreview" src="#" alt="Preview" style="display:none; max-width:100%; max-height:100%; object-fit:cover; border-radius:8px;">
                        </div>
                        <div>
                            <input type="file" name="profile_picture" id="profilePicInput" class="form-control form-control-sm" accept="image/*" onchange="previewFile()">
                            <small class="text-muted d-block mt-1">Upload JPEG, PNG or WebP image (Optional)</small>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Row 1: Title, Name, Mobile Number, Address -->
                    <div class="col-md-2">
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

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold"><span class="text-danger">*</span> Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Customer Name" required autofocus>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold"><span class="text-danger">*</span> Mobile Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g. 0771234567" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Street Address">
                    </div>

                    <!-- Row 2: Credit Limit, Credit Period, NIC, Opening Balance -->
                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Credit Limit (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="credit_limit" class="form-control" placeholder="0.00">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Credit Period</label>
                        <select name="credit_period" class="form-select">
                            <option value="7 Days">7 Days</option>
                            <option value="15 Days">15 Days</option>
                            <option value="30 Days" selected>30 Days</option>
                            <option value="60 Days">60 Days</option>
                            <option value="90 Days">90 Days</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">NIC Number</label>
                        <input type="text" name="nic" class="form-control" placeholder="e.g. 199012345678 / 901234567V">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Opening Balance (Rs.)</label>
                        <input type="number" step="0.01" name="opening_balance" class="form-control" placeholder="0.00">
                    </div>

                    <!-- Row 3: Branch, Vat Enabled, Vat Number -->
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Branch</label>
                        <select name="branch" class="form-select">
                            <option value="Main Branch">Main Branch</option>
                            <option value="City Branch">City Branch</option>
                            <option value="Westside Branch">Westside Branch</option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-center pt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="vat_enabled" id="vatEnabled" onchange="toggleVatNumber()">
                            <label class="form-check-label font-weight-bold" for="vatEnabled">
                                Vat Enabled
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Vat Number</label>
                        <input type="text" name="vat_number" id="vatNumberInput" class="form-control" placeholder="Vat Number" disabled>
                    </div>

                    <!-- Row 4: Category Specific Discounts (Biscuits & Other) -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold text-primary"><i class="fa-solid fa-cookie me-1"></i> Special Discount % (for Biscuits)</label>
                        <input type="number" step="0.01" min="0" max="100" name="discount_biscuits" class="form-control border-primary" placeholder="0.00%">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-weight-bold text-success"><i class="fa-solid fa-layer-group me-1"></i> Special Discount % (for Other items)</label>
                        <input type="number" step="0.01" min="0" max="100" name="discount_other" class="form-control border-success" placeholder="0.00%">
                    </div>

                    <!-- Row 5: Email & User -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="customer@example.com">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">User / Registered By</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars(getCurrentUserName() . ' (' . strtoupper(getCurrentUserRole()) . ')'); ?>" readonly>
                    </div>

                    <div class="col-12">
                        <label class="form-label font-weight-bold">Additional Notes / Preferences</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Birthday, Anniversary, Eggless/Nut Allergy preference"></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 text-end mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <a href="<?php echo BASE_URL; ?>modules/customers/index.php" class="btn btn-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Customer
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
    const icon = document.getElementById('previewIcon');
    const file = document.getElementById('profilePicInput').files[0];
    const reader = new FileReader();

    reader.addEventListener("load", function () {
        preview.src = reader.result;
        preview.style.display = "block";
        icon.style.display = "none";
    }, false);

    if (file) {
        reader.readAsDataURL(file);
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
