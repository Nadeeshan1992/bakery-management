<?php
// install.php - One-click setup script for Bakery Management System

$host = 'localhost';
$user = 'root';
$pass = '';
$message = '';
$success = false;

if (isset($_POST['install']) || (php_sapi_name() === 'cli')) {
    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlFile = __DIR__ . '/database/schema.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("schema.sql file not found in database/ directory.");
        }

        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);

        // Also hash sample passwords cleanly
        $dbPdo = new PDO("mysql:host=$host;dbname=bakery_db;charset=utf8mb4", $user, $pass);
        $hashedPass = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $dbPdo->prepare("UPDATE users SET password = ? WHERE password LIKE '$2y$%' OR password = '' OR password IS NULL");
        $stmt->execute([$hashedPass]);

        $message = "Database 'bakery_db' and seed data created successfully!";
        $success = true;

        if (php_sapi_name() === 'cli') {
            echo "Installation Successful: $message\n";
            exit;
        }
    } catch (Exception $e) {
        $message = "Installation Failed: " . $e->getMessage();
        if (php_sapi_name() === 'cli') {
            echo "Error: $message\n";
            exit(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup & Install - MLB POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #fdf8f5; color: #4a3b32; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .install-card { max-width: 550px; margin: 80px auto; border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: #ffffff; }
        .brand-header { background: linear-gradient(135deg, #d97724, #b25310); color: white; border-top-left-radius: 16px; border-top-right-radius: 16px; padding: 30px; text-align: center; }
        .btn-bakery { background-color: #d97724; color: white; border: none; padding: 12px 24px; font-weight: 600; border-radius: 8px; }
        .btn-bakery:hover { background-color: #b25310; color: white; }
    </style>
</head>
<body>
<div class="container">
    <div class="card install-card">
        <div class="brand-header">
            <img src="assets/images/mlb_logo.jpg" alt="MLB Logo" class="mb-2" style="height: 75px; border-radius: 10px; background: #ffffff; padding: 4px;">
            <h2>MLB POS System</h2>
            <p class="mb-0">Database Auto-Installer</p>
        </div>
        <div class="card-body p-4">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> d-flex align-items-center">
                    <i class="fa-solid fa-<?php echo $success ? 'circle-check' : 'circle-exclamation'; ?> me-2 fs-4"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="text-center my-4">
                    <p class="text-muted">Default user credentials created:</p>
                    <div class="table-responsive">
                        <table class="table table-bordered text-start text-sm">
                            <thead class="table-light">
                                <tr><th>Role</th><th>Username</th><th>Password</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><span class="badge bg-primary">Admin</span></td><td><code>admin</code></td><td><code>password123</code></td></tr>
                                <tr><td><span class="badge bg-success">Cashier</span></td><td><code>cashier</code></td><td><code>password123</code></td></tr>
                                <tr><td><span class="badge bg-warning text-dark">Baker</span></td><td><code>baker</code></td><td><code>password123</code></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <a href="index.php" class="btn btn-bakery w-100 mt-3">Go to Login Page <i class="fa-solid fa-arrow-right ms-2"></i></a>
                </div>
            <?php else: ?>
                <p>Click the button below to initialize the database (<strong>bakery_db</strong>) on your local MySQL server (XAMPP localhost:root).</p>
                <form method="POST">
                    <button type="submit" name="install" value="1" class="btn btn-bakery w-100 py-3 mt-2">
                        <i class="fa-solid fa-database me-2"></i> Run Database Installer
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
