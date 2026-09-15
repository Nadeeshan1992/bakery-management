<?php
// includes/functions.php - Helper utility functions

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatMoney($amount, $currency = 'Rs. ') {
    return $currency . number_format((float)$amount, 2, '.', ',');
}

function formatDate($dateStr, $format = 'M d, Y') {
    if (empty($dateStr)) return 'N/A';
    return date($format, strtotime($dateStr));
}

function formatDateTime($dateStr) {
    if (empty($dateStr)) return 'N/A';
    return date('M d, Y h:i A', strtotime($dateStr));
}

function setFlash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

function displayFlash() {
    $types = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
    $output = '';
    foreach ($types as $key => $bsClass) {
        if (isset($_SESSION['flash_' . $key])) {
            $output .= '<div class="alert alert-' . $bsClass . ' alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                <i class="fa-solid fa-circle-info me-2 fs-5"></i>
                <div>' . htmlspecialchars($_SESSION['flash_' . $key]) . '</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
            unset($_SESSION['flash_' . $key]);
        }
    }
    return $output;
}

function getLowStockIngredientsCount() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM ingredients WHERE current_stock <= min_stock_alert");
        $res = $stmt->fetch();
        return $res['cnt'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

function getPendingOrdersCount() {
    try {
        $db = getDB();
        if (getCurrentUserRole() === 'sales_person') {
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM orders WHERE order_status IN ('pending', 'in_production') AND created_by = ?");
            $stmt->execute([getCurrentUserId()]);
            $res = $stmt->fetch();
        } else {
            $stmt = $db->query("SELECT COUNT(*) as cnt FROM orders WHERE order_status IN ('pending', 'in_production')");
            $res = $stmt->fetch();
        }
        return $res['cnt'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

function getStatusBadge($status) {
    switch (strtolower($status)) {
        case 'completed':
        case 'paid':
        case 'active':
            return '<span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> ' . ucfirst($status) . '</span>';
        case 'in_production':
        case 'partial':
            return '<span class="badge bg-primary"><i class="fa-solid fa-cookie me-1"></i> In Production</span>';
        case 'ready':
            return '<span class="badge bg-info text-dark"><i class="fa-solid fa-box-open me-1"></i> Ready</span>';
        case 'pending':
        case 'unpaid':
            return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i> Pending</span>';
        case 'cancelled':
        case 'inactive':
            return '<span class="badge bg-danger"><i class="fa-solid fa-ban me-1"></i> ' . ucfirst($status) . '</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

function generateAutoSKU($productName, $db = null) {
    if (empty(trim($productName))) {
        return 'PRD' . rand(1000, 9999);
    }
    
    $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', trim($productName));
    $allWords = array_values(array_filter(explode(' ', $clean)));
    $letterWords = array_values(array_filter($allWords, function($w) {
        return preg_match('/^[a-zA-Z]/', $w);
    }));
    
    $words = !empty($letterWords) ? $letterWords : $allWords;
    $prefix = '';
    
    if (count($words) >= 2) {
        $prefix = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        if (isset($words[2])) {
            $prefix .= strtoupper(substr($words[2], 0, 1));
        }
    } elseif (count($words) === 1) {
        $prefix = strtoupper(substr($words[0], 0, 3));
    }
    
    if (empty($prefix)) {
        $prefix = 'PRD';
    }
    
    if ($db) {
        try {
            $counter = 1001;
            do {
                $candidate = $prefix . sprintf('%04d', $counter);
                $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
                $stmt->execute([$candidate]);
                $exists = $stmt->fetchColumn();
                if (!$exists) {
                    return $candidate;
                }
                $counter++;
            } while ($counter < 9999);
        } catch (Exception $e) {
            // fallback
        }
    }
    
    return $prefix . '1001';
}
