<?php
// modules/pos/index.php - POS Counter Billing Interface
require_once __DIR__ . '/../../includes/header.php';

requireRole(['admin', 'owner', 'pos_operator']);

$db = getDB();

// Fetch Categories
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Fetch Active Products
$products = $db->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.name ASC")->fetchAll();

// Fetch Customers for dropdown
$customers = $db->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll();
?>

<div class="row g-3">
    <!-- Left Column: Categories & Products Grid -->
    <div class="col-lg-7 col-xl-7 col-xxl-8">
        <div class="card card-bakery p-3 mb-2 shadow-sm">
            <!-- Search & Filters -->
            <div class="row g-2 mb-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="posSearchInput" class="form-control" placeholder="Search product name or SKU code...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="customerSelect" form="posForm" name="customer_id" class="form-select form-select-sm">
                        <?php foreach ($customers as $c): ?>
                            <?php 
                                $discBisc = floatval($c['discount_biscuits'] ?? $c['special_discount'] ?? 0);
                                $discOther = floatval($c['discount_other'] ?? $c['special_discount'] ?? 0);
                            ?>
                            <option value="<?php echo $c['id']; ?>" 
                                    data-discount-biscuits="<?php echo $discBisc; ?>" 
                                    data-discount-other="<?php echo $discOther; ?>">
                                <?php echo htmlspecialchars(($c['title'] ?? '') . ' ' . $c['name']) . ($c['phone'] !== 'N/A' && !empty($c['phone']) ? ' (' . $c['phone'] . ')' : ''); ?>
                                <?php if ($discBisc > 0 || $discOther > 0): ?> (Disc: B:<?php echo $discBisc; ?>%, O:<?php echo $discOther; ?>%)<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <a href="<?php echo BASE_URL; ?>modules/orders/index.php?type=pos" class="btn btn-sm btn-outline-dark fw-bold w-100 text-nowrap">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> View POS Bills
                    </a>
                </div>
            </div>

            <!-- Category Pills -->
            <div class="d-flex gap-2 overflow-auto pb-1 pos-cat-pills">
                <div class="category-pill active" data-category="all">All Items</div>
                <?php foreach ($categories as $cat): ?>
                    <div class="category-pill" data-category="<?php echo $cat['id']; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="row g-2 overflow-auto pos-products-grid" style="max-height: calc(100vh - 215px);">
            <?php foreach ($products as $p): ?>
                <?php $effectivePrice = ($p['price_retail'] > 0) ? $p['price_retail'] : $p['price']; ?>
                <div class="col-sm-6 col-md-4 col-xl-4 col-xxl-3 pos-product-item" 
                     data-category-id="<?php echo $p['category_id']; ?>" 
                     data-category-name="<?php echo htmlspecialchars($p['category_name']); ?>" 
                     data-name="<?php echo htmlspecialchars($p['name']); ?>" 
                     data-sku="<?php echo htmlspecialchars($p['sku']); ?>">
                    <div class="product-card h-100 <?php echo ($p['current_stock'] <= 0) ? 'opacity-50' : ''; ?>" onclick="addToCart(<?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['name'])); ?>', <?php echo $effectivePrice; ?>, '<?php echo addslashes(htmlspecialchars($p['category_name'])); ?>', <?php echo floatval($p['current_stock']); ?>)">
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;"><?php echo htmlspecialchars($p['sku']); ?></span>
                                    <?php if ($p['current_stock'] <= 0): ?>
                                        <span class="badge bg-danger" style="font-size: 0.68rem;">Out of Stock</span>
                                    <?php elseif ($p['current_stock'] <= $p['min_stock_alert']): ?>
                                        <span class="badge bg-warning text-dark" style="font-size: 0.68rem;">Stock: <?php echo number_format($p['current_stock'], 0); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success" style="font-size: 0.68rem;">Stock: <?php echo number_format($p['current_stock'], 0); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark text-truncate" style="font-size: 0.88rem;" title="<?php echo htmlspecialchars($p['name']); ?>"><?php echo htmlspecialchars($p['name']); ?></h6>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="fw-bold text-success" style="font-size: 1.1rem;"><?php echo formatMoney($effectivePrice); ?></span>
                                <span class="btn btn-sm btn-outline-warning text-dark px-2 py-0"><i class="fa-solid fa-plus"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right Column: POS Cart & Checkout Panel -->
    <div class="col-lg-5 col-xl-5 col-xxl-4">
        <div class="pos-cart-container shadow-sm">
            <!-- Cart Header -->
            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center bg-light" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <div class="d-flex align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-cart-shopping text-warning me-2"></i> Current Cart</h6>
                    <span class="badge bg-warning text-dark ms-2 fw-bold" id="cartCountBadge" style="font-size: 0.72rem;">0 items</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 text-xs" onclick="clearCart()">
                    <i class="fa-solid fa-trash-can me-1"></i> Clear
                </button>
            </div>

            <!-- Cart Table Column Header Bar -->
            <div class="px-3 py-1 bg-white border-bottom text-muted text-xs fw-bold d-flex justify-content-between align-items-center" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                <span style="flex: 1; min-width: 0;">ITEM NAME</span>
                <span class="text-center flex-shrink-0" style="width: 95px;">QTY</span>
                <span class="text-end flex-shrink-0" style="width: 80px;">SUBTOTAL</span>
                <span class="flex-shrink-0" style="width: 24px;"></span>
            </div>

            <!-- Cart Items Scrollable List -->
            <div id="cartItemsContainer" class="pos-cart-items">
                <div class="text-center text-muted py-4">
                    <i class="fa-solid fa-basket-shopping fa-2x mb-2 text-secondary opacity-50"></i>
                    <p class="mb-0 text-xs">Cart is currently empty.<br>Click products on the left to add items.</p>
                </div>
            </div>

            <!-- Checkout Form & Totals Footer -->
            <form id="posForm" action="<?php echo BASE_URL; ?>modules/pos/save_order.php" method="POST" class="pos-cart-footer">
                <input type="hidden" name="cart_data" id="cartJsonInput">

                <!-- Row 1: Discount & Payment Method -->
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label text-xs fw-semibold text-muted mb-0">Discount (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="discount" id="discountAmount" value="0.00" class="form-control form-control-sm text-end fw-semibold">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-xs fw-semibold text-muted mb-0">Payment Method</label>
                        <select name="payment_method" id="posPaymentMethodSelect" class="form-select form-select-sm" onchange="togglePosChequeRefField()">
                            <option value="cash" selected>Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online Transfer</option>
                        </select>
                    </div>
                </div>

                <!-- Cheque Reference No. (Conditional) -->
                <div class="mb-2" id="posChequeRefContainer" style="display: none;">
                    <label class="form-label text-xs fw-semibold text-primary mb-0">Cheque / Reference No. <span class="text-danger">*</span></label>
                    <input type="text" name="cheque_ref" id="posChequeRefInput" class="form-control form-control-sm border-primary" placeholder="Enter cheque number (e.g. CHQ-984712)">
                </div>

                <!-- Row 2: Tendered Amount & Change Due -->
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label text-xs fw-semibold text-muted mb-0">Amount Tendered (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="paid_amount" id="paidAmount" value="0.00" class="form-control form-control-sm text-end fw-bold text-success" placeholder="Cash paid">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-xs fw-semibold text-muted mb-0">Change Due</label>
                        <div id="posChange" class="form-control form-control-sm bg-white fw-bold text-primary text-end">Rs. 0.00</div>
                    </div>
                </div>

                <!-- Live Financial Summary Box -->
                <div class="bg-white p-2 rounded border mb-2">
                    <div class="d-flex justify-content-between text-xs text-muted mb-1">
                        <span>Items Subtotal:</span>
                        <strong id="posSubtotal" class="text-dark">Rs. 0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top pt-1">
                        <span class="fw-bold text-dark fs-6">Net Payable:</span>
                        <strong id="posTotal" class="text-success fs-5 fw-bold">Rs. 0.00</strong>
                    </div>
                </div>

                <!-- Checkout Action Button -->
                <button type="submit" class="btn btn-warning text-dark w-100 py-2 fw-bold shadow-sm" style="font-size: 1rem;">
                    <i class="fa-solid fa-check-circle me-1"></i> Complete Sale & Print Invoice
                </button>
            </form>
        </div>
    </div>
<script>
function togglePosChequeRefField() {
    const select = document.getElementById('posPaymentMethodSelect');
    const container = document.getElementById('posChequeRefContainer');
    const input = document.getElementById('posChequeRefInput');
    if (!select || !container || !input) return;

    if (select.value === 'cheque') {
        container.style.display = 'block';
        input.required = true;
        input.placeholder = "Enter cheque number (e.g. CHQ-984712)";
    } else if (select.value === 'online') {
        container.style.display = 'block';
        input.required = false;
        input.placeholder = "Enter online transfer reference no. (Optional)";
    } else {
        container.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
