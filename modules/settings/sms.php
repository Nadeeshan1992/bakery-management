<?php
// modules/settings/sms.php - SMS Gateway Settings & Delivery Logs
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sms_helper.php';

requireRole(['admin', 'owner']);

$db = getDB();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action 1: Save Settings
    if ($action === 'save_settings') {
        $provider = $_POST['gateway_provider'] ?? 'mock_local';
        $apiUrl = trim($_POST['api_url'] ?? 'https://app.notify.lk/api/v1/send');
        $apiKey = trim($_POST['api_key'] ?? '');
        $userId = trim($_POST['user_id'] ?? '');
        $senderId = trim($_POST['sender_id'] ?? 'MLB-BAKERY');
        $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
        $sendPreorder = isset($_POST['send_on_preorder']) ? 1 : 0;
        $sendPayment = isset($_POST['send_on_payment']) ? 1 : 0;
        $preorderTpl = trim($_POST['preorder_template'] ?? '');
        $paymentTpl = trim($_POST['payment_template'] ?? '');

        $stmt = $db->prepare("
            UPDATE sms_settings 
            SET gateway_provider = ?, api_url = ?, api_key = ?, user_id = ?, sender_id = ?, 
                is_enabled = ?, send_on_preorder = ?, send_on_payment = ?, 
                preorder_template = ?, payment_template = ?
            WHERE id = (SELECT id FROM (SELECT id FROM sms_settings ORDER BY id ASC LIMIT 1) as t)
        ");
        $stmt->execute([
            $provider, $apiUrl, $apiKey, $userId, $senderId,
            $isEnabled, $sendPreorder, $sendPayment,
            $preorderTpl, $paymentTpl
        ]);

        setFlash('success', 'SMS Gateway configuration updated successfully!');
        header('Location: ' . BASE_URL . 'modules/settings/sms.php');
        exit;
    }

    // Action 2: Send Test SMS
    if ($action === 'test_sms') {
        $testPhone = trim($_POST['test_phone'] ?? '');
        $testMsg = trim($_POST['test_message'] ?? 'MLB Bakery: This is a test SMS notification from your POS system.');

        if (!empty($testPhone)) {
            $result = sendSMS($testPhone, $testMsg, 'test_sms', null, null, getCurrentUserId());
            if ($result['success']) {
                setFlash('success', "Test SMS sent to {$testPhone}! Status: " . strtoupper($result['status']));
            } else {
                setFlash('error', "Failed to send Test SMS: " . $result['message']);
            }
        } else {
            setFlash('error', 'Please enter a valid mobile number.');
        }
        header('Location: ' . BASE_URL . 'modules/settings/sms.php');
        exit;
    }
}

$settings = getSmsSettings($db);

// Fetch recent 50 SMS logs
$logs = $db->query("
    SELECT l.*, u.username as sent_by_user 
    FROM sms_logs l 
    LEFT JOIN users u ON l.sent_by = u.id 
    ORDER BY l.id DESC 
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

// Counts
$countSent = $db->query("SELECT COUNT(*) FROM sms_logs WHERE status IN ('sent', 'mock_sent')")->fetchColumn();
$countFailed = $db->query("SELECT COUNT(*) FROM sms_logs WHERE status = 'failed'")->fetchColumn();
$countTotal = $db->query("SELECT COUNT(*) FROM sms_logs")->fetchColumn();
?>

<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-comment-sms text-warning me-2"></i> SMS Gateway Configuration</h4>
            <small class="text-muted">Manage automated customer SMS alerts for Pre-Orders & Payment Receipts</small>
        </div>
        <div>
            <span class="badge <?php echo !empty($settings['is_enabled']) ? 'bg-success' : 'bg-secondary'; ?> px-3 py-2 fs-6 rounded-pill">
                <i class="fa-solid fa-circle-dot me-1"></i> SMS Gateway: <?php echo !empty($settings['is_enabled']) ? 'ACTIVE' : 'DISABLED'; ?>
            </span>
        </div>
    </div>
</div>

<!-- Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-bakery p-3 text-center">
            <small class="text-muted fw-bold text-uppercase" style="font-size: 0.75rem;">Total SMS Logged</small>
            <h3 class="fw-bold mb-0 text-dark mt-1"><?php echo number_format($countTotal); ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-bakery p-3 text-center">
            <small class="text-success fw-bold text-uppercase" style="font-size: 0.75rem;">Delivered / Mock Sent</small>
            <h3 class="fw-bold mb-0 text-success mt-1"><?php echo number_format($countSent); ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-bakery p-3 text-center">
            <small class="text-danger fw-bold text-uppercase" style="font-size: 0.75rem;">Failed Attempts</small>
            <h3 class="fw-bold mb-0 text-danger mt-1"><?php echo number_format($countFailed); ?></h3>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Left Column: Settings & Templates -->
    <div class="col-lg-8">
        <div class="card card-bakery p-4 mb-4">
            <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">
                <i class="fa-solid fa-sliders text-warning me-2"></i> Gateway Provider & Credentials
            </h5>

            <form method="POST" action="">
                <input type="hidden" name="action" value="save_settings">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">SMS Gateway Provider *</label>
                        <select name="gateway_provider" id="gatewayProviderSelect" class="form-select" onchange="toggleProviderFields()">
                            <option value="mock_local" <?php echo ($settings['gateway_provider'] === 'mock_local') ? 'selected' : ''; ?>>
                                🧪 Mock Test Mode (Free Local Testing - No Credits Required)
                            </option>
                            <option value="notify_lk" <?php echo ($settings['gateway_provider'] === 'notify_lk') ? 'selected' : ''; ?>>
                                🇱🇰 Notify.lk (Sri Lanka Telecom Gateway)
                            </option>
                            <option value="generic_http" <?php echo ($settings['gateway_provider'] === 'generic_http') ? 'selected' : ''; ?>>
                                🌐 Generic HTTP / REST API Gateway
                            </option>
                        </select>
                        <small class="text-muted">Use Mock Test Mode for testing without consuming actual SMS credits.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Sender Mask / Sender ID</label>
                        <input type="text" name="sender_id" class="form-control" value="<?php echo htmlspecialchars($settings['sender_id']); ?>" placeholder="e.g. MLB-BAKERY">
                        <small class="text-muted">Your registered telecom mask or brand name.</small>
                    </div>
                </div>

                <!-- Provider specific fields -->
                <div id="credentialsBox" class="bg-light p-3 rounded border mb-3">
                    <div class="row g-3">
                        <div class="col-md-6" id="userIdCol">
                            <label class="form-label fw-bold">User ID / Account ID</label>
                            <input type="text" name="user_id" class="form-control" value="<?php echo htmlspecialchars($settings['user_id']); ?>" placeholder="Notify.lk User ID">
                        </div>
                        <div class="col-md-6" id="apiKeyCol">
                            <label class="form-label fw-bold">API Key / Token</label>
                            <input type="password" name="api_key" class="form-control" value="<?php echo htmlspecialchars($settings['api_key']); ?>" placeholder="Secret API Key">
                        </div>
                        <div class="col-12" id="apiUrlCol">
                            <label class="form-label fw-bold">API Endpoint URL</label>
                            <input type="url" name="api_url" class="form-control" value="<?php echo htmlspecialchars($settings['api_url']); ?>" placeholder="https://app.notify.lk/api/v1/send">
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold mb-3 text-dark border-bottom pb-2 mt-4">
                    <i class="fa-solid fa-bell text-warning me-2"></i> Automated Triggers & Message Templates
                </h5>

                <div class="mb-3">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_enabled" id="checkEnabled" <?php echo !empty($settings['is_enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="checkEnabled">Enable SMS Gateway Globally</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="send_on_preorder" id="checkPreorder" <?php echo !empty($settings['send_on_preorder']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="checkPreorder">Send SMS when Pre-Order is Booked (Desktop & Mobile)</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="send_on_payment" id="checkPayment" <?php echo !empty($settings['send_on_payment']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="checkPayment">Send SMS when Payment / Settlement is Made (Desktop & Mobile)</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Pre-Order Confirmation Template</label>
                    <textarea name="preorder_template" class="form-control" rows="3"><?php echo htmlspecialchars($settings['preorder_template']); ?></textarea>
                    <small class="text-muted d-block mt-1">
                        <strong>Available tags:</strong> <code>{customer_name}</code>, <code>{order_number}</code>, <code>{total_amount}</code>, <code>{paid_amount}</code>, <code>{balance_due}</code>, <code>{delivery_date}</code>
                    </small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Payment Receipt Template</label>
                    <textarea name="payment_template" class="form-control" rows="3"><?php echo htmlspecialchars($settings['payment_template']); ?></textarea>
                    <small class="text-muted d-block mt-1">
                        <strong>Available tags:</strong> <code>{customer_name}</code>, <code>{order_number}</code>, <code>{paid_amount}</code>, <code>{payment_method}</code>, <code>{balance_due}</code>
                    </small>
                </div>

                <button type="submit" class="btn btn-warning text-dark fw-bold px-4 shadow-sm">
                    <i class="fa-solid fa-save me-1"></i> Save SMS Configuration
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Send Test SMS -->
    <div class="col-lg-4">
        <div class="card card-bakery p-4 mb-4">
            <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">
                <i class="fa-solid fa-paper-plane text-warning me-2"></i> Send Test SMS
            </h5>
            <p class="text-muted small">Verify that your gateway credentials or mock mode deliver messages properly.</p>

            <form method="POST" action="">
                <input type="hidden" name="action" value="test_sms">
                <div class="mb-3">
                    <label class="form-label fw-bold text-xs">Recipient Mobile Number *</label>
                    <input type="text" name="test_phone" class="form-control form-control-sm" placeholder="e.g. 0771234567" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-xs">Test Message</label>
                    <textarea name="test_message" class="form-control form-control-sm" rows="3">MLB Bakery: Hello! This is a test notification from your Bakery POS system.</textarea>
                </div>
                <button type="submit" class="btn btn-outline-dark fw-bold btn-sm w-100 py-2">
                    <i class="fa-solid fa-bolt me-1"></i> Dispatch Test SMS
                </button>
            </form>
        </div>

        <div class="card card-bakery p-3">
            <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-circle-info text-primary me-1"></i> Setup Guide</h6>
            <ul class="text-xs text-muted ps-3 mb-0" style="line-height: 1.6;">
                <li><strong>Mock Test Mode</strong> allows you to build and test orders without spending money on SMS credits.</li>
                <li>To send live messages in Sri Lanka, sign up at <strong>Notify.lk</strong>, get your <code>User ID</code> and <code>API Key</code>, and request your Sender Mask.</li>
                <li>Phone numbers are automatically normalized to <code>94xxxxxxxxx</code> format.</li>
            </ul>
        </div>
    </div>
</div>

<!-- SMS Delivery History Table -->
<div class="card card-bakery p-4 mt-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-clock-rotate-left text-warning me-2"></i> Recent SMS Delivery Logs (Latest 50)
        </h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-sm">
            <thead class="table-light">
                <tr>
                    <th>Date & Time</th>
                    <th>Recipient</th>
                    <th>Event Type</th>
                    <th>Message Snippet</th>
                    <th>Status</th>
                    <th>Triggered By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No SMS notifications recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <?php 
                            $badgeClass = 'bg-secondary';
                            if ($l['status'] === 'sent') $badgeClass = 'bg-success';
                            elseif ($l['status'] === 'mock_sent') $badgeClass = 'bg-info text-dark';
                            elseif ($l['status'] === 'failed') $badgeClass = 'bg-danger';
                            elseif ($l['status'] === 'disabled') $badgeClass = 'bg-warning text-dark';
                        ?>
                        <tr>
                            <td class="text-nowrap text-xs"><?php echo date('d-M-Y H:i', strtotime($l['created_at'])); ?></td>
                            <td class="fw-bold text-xs"><?php echo htmlspecialchars($l['phone_number']); ?></td>
                            <td>
                                <span class="badge bg-light text-dark border text-xs"><?php echo htmlspecialchars($l['event_type']); ?></span>
                            </td>
                            <td class="text-truncate text-xs" style="max-width: 320px;" title="<?php echo htmlspecialchars($l['message']); ?>">
                                <?php echo htmlspecialchars($l['message']); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $badgeClass; ?> text-uppercase text-xs"><?php echo htmlspecialchars($l['status']); ?></span>
                            </td>
                            <td class="text-xs text-muted"><?php echo htmlspecialchars($l['sent_by_user'] ?? 'System / API'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleProviderFields() {
    const sel = document.getElementById('gatewayProviderSelect').value;
    const box = document.getElementById('credentialsBox');
    const userIdCol = document.getElementById('userIdCol');
    const apiUrlCol = document.getElementById('apiUrlCol');

    if (sel === 'mock_local') {
        box.style.display = 'none';
    } else {
        box.style.display = 'block';
        if (sel === 'notify_lk') {
            userIdCol.style.display = 'block';
            apiUrlCol.style.display = 'none';
        } else {
            userIdCol.style.display = 'none';
            apiUrlCol.style.display = 'block';
        }
    }
}
document.addEventListener('DOMContentLoaded', toggleProviderFields);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
