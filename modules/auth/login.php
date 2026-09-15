<?php
// modules/auth/login.php - User Login Page
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];

                setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
                header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bakery Management System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #fdf8f5 0%, #f7e9df 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            border: none;
            border-radius: 18px;
            box-shadow: 0 12px 35px rgba(140, 74, 39, 0.12);
            overflow: hidden;
            background: #ffffff;
        }
        .login-header {
            background: linear-gradient(135deg, #d97724, #8c4a27);
            color: #ffffff;
            padding: 35px 25px 25px;
            text-align: center;
        }
        .login-header i {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .btn-bakery {
            background-color: #d97724;
            border-color: #d97724;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
        }
        .btn-bakery:hover {
            background-color: #b25310;
            border-color: #b25310;
            color: white;
        }
        .demo-credential-box {
            background-color: #faf4f0;
            border-radius: 10px;
            padding: 12px;
            font-size: 0.85rem;
            border: 1px dashed #ebdcd3;
        }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="login-header text-center">
        <img src="<?php echo BASE_URL; ?>assets/images/mlb_logo.jpg" alt="MLB Logo" class="mb-2" style="height: 85px; border-radius: 12px; background: #ffffff; padding: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
        <h3 class="fw-bold mb-0">MLB POS System</h3>
        <p class="mb-0 text-white-50 fs-6">Bakery Portal Login</p>
    </div>
    <div class="card-body p-4">
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center mb-3">
                <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <?php echo displayFlash(); ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label font-weight-bold"><i class="fa-solid fa-user me-1 text-muted"></i> Username</label>
                <input type="text" class="form-control form-control-lg" id="username" name="username" placeholder="Enter username" required autofocus>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label font-weight-bold"><i class="fa-solid fa-lock me-1 text-muted"></i> Password</label>
                <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Enter password" required>
            </div>

            <button type="submit" class="btn btn-bakery w-100 mb-3 fs-6">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Log In
            </button>
        </form>

        <div class="demo-credential-box mt-3 text-center">
            <strong class="d-block mb-1 text-dark"><i class="fa-solid fa-key text-warning me-1"></i> Default Admin Login:</strong>
            <div class="d-flex justify-content-center flex-wrap gap-2 mt-2">
                <button class="btn btn-xs btn-outline-warning text-dark fw-bold text-nowrap" onclick="setDemo('admin')"><i class="fa-solid fa-user-shield me-1"></i> Admin (admin / password123)</button>
            </div>
        </div>
    </div>
</div>

<script>
function setDemo(role) {
    document.getElementById('username').value = role;
    document.getElementById('password').value = 'password123';
}
</script>

</body>
</html>
