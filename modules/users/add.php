<?php
// modules/users/add.php - Add New System User
require_once __DIR__ . '/../../includes/header.php';

// Only Admin / Owner can add users
requireRole(['admin', 'owner']);

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role'] ?? 'pos_operator');
    $status   = trim($_POST['status'] ?? 'active');

    // Validation
    if (empty($fullName) || empty($username) || empty($password)) {
        $error = "Please fill in all required fields (Full Name, Username, Password).";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!in_array($role, ['admin', 'owner', 'sales_person', 'pos_operator'])) {
        $error = "Invalid role selected.";
    } else {
        try {
            // Check if username already exists
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmtCheck->execute([$username]);
            if ($stmtCheck->fetchColumn() > 0) {
                $error = "Username '$username' is already taken. Please choose a different username.";
            } else {
                // Hash password and insert user
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmtInsert = $db->prepare("
                    INSERT INTO users (full_name, username, email, password, role, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmtInsert->execute([$fullName, $username, $email, $passwordHash, $role, $status]);

                setFlash('success', "User '$fullName' ($username) created successfully as " . ucwords(str_replace('_', ' ', $role)) . "!");
                header('Location: ' . BASE_URL . 'modules/users/index.php');
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
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-user-plus text-warning me-2"></i> Add New System User</h4>
        <p class="text-muted small mb-0">Create new login credentials for Sales Persons, POS Operators, or Owners/Admins.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/users/index.php" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to User List
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-bakery p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4">
                    <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row g-3">
                    <!-- Full Name -->
                    <div class="col-md-6">
                        <label for="full_name" class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" placeholder="e.g. John Perera" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                    </div>

                    <!-- Username -->
                    <div class="col-md-6">
                        <label for="username" class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="e.g. john_sales" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        <div class="form-text">Used to log in to the POS system.</div>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-bold">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="e.g. john@bakery.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <!-- Password -->
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimum 6 characters" required>
                    </div>

                    <!-- Role Selection -->
                    <div class="col-md-6">
                        <label for="role" class="form-label fw-bold">User Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin (Full Powers - All Modules, Users & Reports)</option>
                            <option value="owner" <?php echo (($_POST['role'] ?? '') === 'owner') ? 'selected' : ''; ?>>Owner (Business Owner - Full Powers)</option>
                            <option value="sales_person" <?php echo (($_POST['role'] ?? '') === 'sales_person') ? 'selected' : ''; ?>>Sales Person (Pre-Orders, Discounts & Customers)</option>
                            <option value="pos_operator" <?php echo (($_POST['role'] ?? '') === 'pos_operator') ? 'selected' : ''; ?>>POS Operator (Counter Walk-in POS Billing)</option>
                        </select>
                    </div>

                    <!-- Account Status -->
                    <div class="col-md-6">
                        <label for="status" class="form-label fw-bold">Account Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" selected>Active (Can log in)</option>
                            <option value="inactive">Inactive (Disabled)</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo BASE_URL; ?>modules/users/index.php" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-warning text-dark font-weight-bold">
                        <i class="fa-solid fa-check me-1"></i> Save User Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
