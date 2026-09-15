<?php
// modules/users/index.php - User Management List
require_once __DIR__ . '/../../includes/header.php';

// Only Admin / Owner can access User Management
requireRole(['admin', 'owner']);

$db = getDB();

// Fetch all users
$stmt = $db->query("SELECT id, username, full_name, email, role, status, created_at FROM users ORDER BY id ASC");
$users = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-users-gear text-warning me-2"></i> User Management</h4>
        <p class="text-muted small mb-0">Manage system users, assign roles (Admin/Owner, Sales Person, POS Operator), and set active status.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/users/add.php" class="btn btn-warning text-dark font-weight-bold">
        <i class="fa-solid fa-user-plus me-1"></i> Add New User
    </a>
</div>

<div class="card card-bakery p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong>#<?php echo $user['id']; ?></strong></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle bg-light border d-flex align-items-center justify-content-center text-secondary fw-bold" style="width: 35px; height: 35px; border-radius: 50%;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <div>
                                        <strong class="text-dark d-block"><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td><code><?php echo htmlspecialchars($user['username']); ?></code></td>
                            <td><?php echo htmlspecialchars($user['email'] ?: 'N/A'); ?></td>
                            <td>
                                <?php 
                                    $roleBadge = 'bg-secondary';
                                    $roleLabel = ucwords(str_replace('_', ' ', $user['role']));
                                    if ($user['role'] === 'owner') {
                                        $roleBadge = 'bg-dark text-warning border border-warning';
                                        $roleLabel = 'Owner';
                                    } elseif ($user['role'] === 'admin') {
                                        $roleBadge = 'bg-dark text-white';
                                        $roleLabel = 'Admin';
                                    } elseif ($user['role'] === 'sales_person') {
                                        $roleBadge = 'bg-primary text-white';
                                    } elseif ($user['role'] === 'pos_operator') {
                                        $roleBadge = 'bg-success text-white';
                                    }
                                ?>
                                <span class="badge <?php echo $roleBadge; ?> px-2 py-1">
                                    <?php echo $roleLabel; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="fa-solid fa-circle-check me-1"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                        <i class="fa-solid fa-circle-xmark me-1"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted text-sm"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td class="text-end">
                                <a href="<?php echo BASE_URL; ?>modules/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary me-1">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                </a>
                                <?php if ($user['id'] == getCurrentUserId()): ?>
                                    <button class="btn btn-sm btn-light border text-muted" disabled title="Current User">
                                        <i class="fa-solid fa-user-shield me-1"></i> Current User
                                    </button>
                                <?php elseif ($user['status'] === 'active'): ?>
                                    <a href="<?php echo BASE_URL; ?>modules/users/toggle_status.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to deactivate <?php echo htmlspecialchars(addslashes($user['full_name'])); ?>?');">
                                        <i class="fa-solid fa-user-xmark me-1"></i> Deactivate
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>modules/users/toggle_status.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Are you sure you want to activate <?php echo htmlspecialchars(addslashes($user['full_name'])); ?>?');">
                                        <i class="fa-solid fa-user-check me-1"></i> Activate
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
