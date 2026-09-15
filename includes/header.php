<?php
// includes/header.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

requireLogin();

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

$lowStockBadge = getLowStockIngredientsCount();
$pendingOrdersBadge = getPendingOrdersCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLB POS System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>

<div id="wrapper">
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarBackdrop" class="sidebar-backdrop d-none"></div>

    <!-- Sidebar Navigation -->
    <nav id="sidebar">
        <div class="sidebar-brand d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <img src="<?php echo BASE_URL; ?>assets/images/mlb_logo.jpg" alt="MLB Logo" style="height: 38px; border-radius: 6px; background: #ffffff; padding: 2px;">
                <span>MLB POS System</span>
            </div>
            <button class="btn btn-sm btn-link text-white-50 d-md-none p-0 ms-2" id="sidebarCloseBtn" type="button" aria-label="Close menu">
                <i class="fa-solid fa-xmark fa-xl"></i>
            </button>
        </div>

        <ul class="list-unstyled components">
            <li class="<?php echo ($currentDir == 'dashboard') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/dashboard/index.php">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
            </li>
            <?php if (in_array(getCurrentUserRole(), ['admin', 'owner', 'pos_operator'])): ?>
            <li class="<?php echo ($currentDir == 'pos') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/pos/index.php">
                    <i class="fa-solid fa-cash-register"></i> POS Counter
                </a>
            </li>
            <?php endif; ?>
            <li class="<?php echo ($currentDir == 'orders') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/orders/index.php" class="d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-calendar-check"></i> Pre-Orders</span>
                    <?php if ($pendingOrdersBadge > 0): ?>
                        <span class="badge bg-warning text-dark me-2"><?php echo $pendingOrdersBadge; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if (in_array(getCurrentUserRole(), ['admin', 'owner', 'pos_operator'])): ?>
            <li class="<?php echo ($currentDir == 'products' && basename($_SERVER['PHP_SELF']) != 'daily_stock.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/products/index.php">
                    <i class="fa-solid fa-bread-slice"></i> Products & Catalog
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'daily_stock.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/products/daily_stock.php">
                    <i class="fa-solid fa-boxes-packing text-warning"></i> Daily Item Stock In
                </a>
            </li>
            <li class="<?php echo ($currentDir == 'inventory') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/inventory/index.php" class="d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-boxes-stacked"></i> Raw Inventory</span>
                    <?php if ($lowStockBadge > 0): ?>
                        <span class="badge bg-danger me-2"><?php echo $lowStockBadge; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="<?php echo ($currentDir == 'production') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/production/index.php">
                    <i class="fa-solid fa-fire-burner"></i> Recipes & Baking
                </a>
            </li>
            <?php endif; ?>
            <li class="<?php echo ($currentDir == 'customers') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/customers/index.php">
                    <i class="fa-solid fa-users"></i> Customers
                </a>
            </li>
            <?php if (in_array(getCurrentUserRole(), ['admin', 'owner'])): ?>
            <li class="<?php echo ($currentDir == 'staff') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/staff/index.php">
                    <i class="fa-solid fa-id-card"></i> Staff Members
                </a>
            </li>
            <li class="<?php echo ($currentDir == 'payroll') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/payroll/index.php">
                    <i class="fa-solid fa-money-check-dollar"></i> Payroll & Salary
                </a>
            </li>
            <li class="<?php echo ($currentDir == 'reports') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/reports/index.php">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Sales Reports
                </a>
            </li>
            <li class="<?php echo ($currentDir == 'users') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/users/index.php">
                    <i class="fa-solid fa-users-gear"></i> User Management
                </a>
            </li>
            <?php endif; ?>
            <?php if (getCurrentUserRole() === 'admin'): ?>
            <li class="<?php echo ($currentDir == 'settings' && $currentPage == 'sms.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>modules/settings/sms.php">
                    <i class="fa-solid fa-comment-sms text-warning"></i> SMS Gateway
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <div class="mt-auto p-3 text-center border-top border-secondary">
            <small class="text-muted">Role: <strong class="text-light"><?php echo strtoupper(str_replace('_', ' ', getCurrentUserRole())); ?></strong></small>
        </div>
    </nav>

    <!-- Page Content Wrapper -->
    <div id="content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg top-navbar d-flex justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary d-md-none p-1 px-2 me-1" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                    <i class="fa-solid fa-bars fa-lg"></i>
                </button>
                <h5 class="mb-0 text-capitalize font-weight-bold" style="font-size: 1.05rem;">
                    <?php echo ($currentDir == 'pos') ? 'Point of Sale (POS)' : ucfirst($currentDir); ?>
                </h5>
            </div>

            <div class="d-flex align-items-center gap-2">
                <?php if (in_array(getCurrentUserRole(), ['admin', 'owner', 'pos_operator'])): ?>
                <a href="<?php echo BASE_URL; ?>modules/pos/index.php" class="btn btn-sm btn-outline-warning text-dark fw-semibold">
                    <i class="fa-solid fa-bolt"></i> <span class="d-none d-sm-inline ms-1">Quick POS</span>
                </a>
                <?php endif; ?>

                <a href="<?php echo BASE_URL; ?>mobile_app/index.html" target="_blank" class="btn btn-sm btn-outline-primary fw-semibold">
                    <i class="fa-solid fa-mobile-screen-button"></i> <span class="d-none d-sm-inline ms-1">Mobile App</span>
                </a>

                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border btn-sm" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-circle-user text-secondary"></i>
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars(getCurrentUserName()); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user-gear me-2"></i> Role: <?php echo ucwords(str_replace('_', ' ', getCurrentUserRole())); ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>modules/auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="main-container <?php echo ($currentDir == 'pos') ? 'p-3 py-2' : ''; ?>">
            <?php echo displayFlash(); ?>
