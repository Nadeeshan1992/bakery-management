<?php
// modules/users/toggle_status.php - Toggle User Active / Inactive Status
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
requireRole(['admin', 'owner']);

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    if ($id == getCurrentUserId()) {
        setFlash('error', 'You cannot deactivate your own active account while logged in!');
    } else {
        $stmt = $db->prepare("SELECT status, full_name, username FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if ($user) {
            $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
            $stmtUpdate = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $id]);
            
            $actionLabel = ($newStatus === 'active') ? 'activated' : 'deactivated';
            setFlash('success', 'User "' . htmlspecialchars($user['full_name']) . '" (' . htmlspecialchars($user['username']) . ') has been ' . $actionLabel . ' successfully!');
        } else {
            setFlash('error', 'User not found.');
        }
    }
}

header('Location: ' . BASE_URL . 'modules/users/index.php');
exit;
