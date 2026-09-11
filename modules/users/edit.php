<?php
// modules/users/edit.php - Edit System User Profile & Role
require_once __DIR__ . '/../../includes/header.php';

// Only Admin / Owner can edit users
requireRole(['admin', 'owner']);

$db = getDB();
$userId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('danger', 'User not found.');
    header('Location: ' . BASE_URL . 'modules/users/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role'] ?? 'pos_operator');
    $status   = trim($_POST['status'] ?? 'active');

    // Validation
    if (empty($fullName) || empty($username)) {
        $error = "Please fill in all required fields (Full Name, Username).";
    } elseif (!in_array($role, ['admin', 'owner', 'sales_person', 'pos_operator'])) {
        $error = "Invalid role selected.";
    } else {
        try {
            // Check if username is taken by another user
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmtCheck->execute([$username, $userId]);
            if ($stmtCheck->fetchColumn() > 0) {
                $error = "Username '$username' is already taken by another account.";
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error = "Password must be at least 6 characters long.";
                    } else {
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $stmtUpdate = $db->prepare("
                            UPDATE users SET full_name = ?, username = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?
                        ");
                        $stmtUpdate->execute([$fullName, $username, $email, $passwordHash, $role, $status, $userId]);
                    }
                } else {
                    $stmtUpdate = $db->prepare("
                        UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, status = ? WHERE id = ?
                    ");
                    $stmtUpdate->execute([$fullName, $username, $email, $role, $status, $userId]);
                }

                if (empty($error)) {
                    setFlash('success', "User account for '$fullName' updated successfully!");
                    header('Location: ' . BASE_URL . 'modules/users/index.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-user-pen text-warning me-2"></i> Edit User Account</h4>
        <p class="text-muted small mb-0">Modify user role, update personal details, or reset password for <?php echo htmlspecialchars($user['full_name']); ?>.</p>
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
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? $user['full_name']); ?>" required>
                    </div>

                    <!-- Username -->
                    <div class="col-md-6">
                        <label for="username" class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? $user['username']); ?>" required>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-bold">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>">
                    </div>

                    <!-- Change Password (Optional) -->
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-bold">New Password (Optional)</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep existing password">
                    </div>

                    <!-- Role Selection -->
                    <div class="col-md-6">
                        <label for="role" class="form-label fw-bold">User Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin" <?php echo (($user['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin (Full Powers - All Modules, Users & Reports)</option>
                            <option value="owner" <?php echo (($user['role'] ?? '') === 'owner') ? 'selected' : ''; ?>>Owner (Business Owner - Full Powers)</option>
                            <option value="sales_person" <?php echo (($user['role'] ?? '') === 'sales_person') ? 'selected' : ''; ?>>Sales Person (Pre-Orders, Discounts & Customers)</option>
                            <option value="pos_operator" <?php echo (($user['role'] ?? '') === 'pos_operator') ? 'selected' : ''; ?>>POS Operator (Counter Walk-in POS Billing)</option>
                        </select>
                    </div>

                    <!-- Account Status -->
                    <div class="col-md-6">
                        <label for="status" class="form-label fw-bold">Account Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo ($user['status'] === 'active') ? 'selected' : ''; ?>>Active (Can log in)</option>
                            <option value="inactive" <?php echo ($user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive (Disabled)</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo BASE_URL; ?>modules/users/index.php" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-warning text-dark font-weight-bold">
                        <i class="fa-solid fa-check me-1"></i> Update User Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
