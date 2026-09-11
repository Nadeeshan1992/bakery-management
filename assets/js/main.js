/* main.js - Client Side Bakery POS & UI interactivity */

let cart = [];

document.addEventListener('DOMContentLoaded', function () {
    // Enable Bootstrap Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Mobile Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    function openMobileSidebar() {
        if (sidebar) sidebar.classList.add('active');
        if (sidebarBackdrop) sidebarBackdrop.classList.remove('d-none');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
        if (sidebar) sidebar.classList.remove('active');
        if (sidebarBackdrop) sidebarBackdrop.classList.add('d-none');
        document.body.style.overflow = '';
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', openMobileSidebar);
    }
    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', closeMobileSidebar);
    }
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeMobileSidebar);
    }

    // Category Filter in POS
    const catPills = document.querySelectorAll('.category-pill');
    catPills.forEach(pill => {
        pill.addEventListener('click', function () {
            catPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');

            const catId = this.getAttribute('data-category');
            const items = document.querySelectorAll('.pos-product-item');

            items.forEach(item => {
                if (catId === 'all' || item.getAttribute('data-category-id') === catId) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // Product Search Filter in POS
    const searchInput = document.getElementById('posSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const items = document.querySelectorAll('.pos-product-item');

            items.forEach(item => {
                const name = item.getAttribute('data-name').toLowerCase();
                const sku = item.getAttribute('data-sku').toLowerCase();
                if (name.includes(query) || sku.includes(query)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Payment Change Calculator in POS
    const paidInput = document.getElementById('paidAmount');
    if (paidInput) {
        paidInput.addEventListener('input', updatePOSCalculations);
    }
    const discountInput = document.getElementById('discountAmount');
    if (discountInput) {
        discountInput.addEventListener('input', updatePOSCalculations);
    }

    // Customer Select Listener
    const custSelect = document.getElementById('customerSelect');
    if (custSelect) {
        custSelect.addEventListener('change', handleCustomerChange);
    }
});

// Add Product to Cart with Out-of-Stock Validation
function addToCart(id, name, price, categoryName, stock) {
    price = parseFloat(price);
    categoryName = categoryName || '';
    stock = parseFloat(stock !== undefined ? stock : 9999);

    if (stock <= 0) {
        alert(`Cannot add to invoice: "${name}" is OUT OF STOCK! Please log daily stock first.`);
        return;
    }

    const existingIndex = cart.findIndex(item => item.id === id);

    if (existingIndex > -1) {
        if (cart[existingIndex].qty + 1 > stock) {
            alert(`Stock limit reached! Only ${stock} pcs available for "${name}".`);
            return;
        }
        cart[existingIndex].qty += 1;
    } else {
        cart.push({
            id: id,
            name: name,
            price: price,
            category: categoryName,
            stock: stock,
            qty: 1
        });
    }
    renderCart();

    // Auto Focus and Select the Quantity Input for immediate typing
    setTimeout(() => {
        const qtyInput = document.getElementById(`cart-qty-${id}`);
        if (qtyInput) {
            qtyInput.focus();
            qtyInput.select();
        }
    }, 50);
}

// Directly Set Item Quantity from Manual Typing with Stock Check
function setCartQty(id, val) {
    const qty = parseInt(val);
    const index = cart.findIndex(item => item.id === id);

    if (index > -1) {
        const stock = cart[index].stock || 9999;
        if (!isNaN(qty) && qty > 0) {
            if (qty > stock) {
                alert(`Stock limit exceeded! Only ${stock} pcs available for "${cart[index].name}".`);
                cart[index].qty = stock;
                const inputEl = document.getElementById(`cart-qty-${id}`);
                if (inputEl) inputEl.value = stock;
            } else {
                cart[index].qty = qty;
            }
        } else if (val === '') {
            cart[index].qty = 1; // Fallback draft
        }
    }

    // Update item subtotal text without losing focus
    const item = cart[index];
    if (item) {
        const subtotalEl = document.getElementById(`cart-item-subtotal-${id}`);
        if (subtotalEl) {
            subtotalEl.innerText = 'Rs. ' + (item.price * item.qty).toFixed(2);
        }
    }

    document.getElementById('cartJsonInput').value = JSON.stringify(cart);
    updatePOSCalculations();
}

// Step Quantity (+ / - buttons) with Stock Check
function stepQty(id, change) {
    const index = cart.findIndex(item => item.id === id);
    if (index > -1) {
        const stock = cart[index].stock || 9999;
        const newQty = cart[index].qty + change;
        if (newQty > stock) {
            alert(`Stock limit reached! Only ${stock} pcs available for "${cart[index].name}".`);
            return;
        }
        cart[index].qty = Math.max(1, newQty);
        renderCart();
        
        // Refocus input
        setTimeout(() => {
            const qtyInput = document.getElementById(`cart-qty-${id}`);
            if (qtyInput) {
                qtyInput.focus();
                qtyInput.select();
            }
        }, 50);
    }
}

// Remove Item from Cart
function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    renderCart();
}

// Clear Cart
function clearCart() {
    cart = [];
    renderCart();
}

// Handle Customer Category-Specific Discounts (Biscuits vs Other)
function handleCustomerChange() {
    const custSelect = document.getElementById('customerSelect');
    if (!custSelect) return;

    const selectedOption = custSelect.options[custSelect.selectedIndex];
    const discBiscuitsPercent = parseFloat(selectedOption?.getAttribute('data-discount-biscuits') || 0);
    const discOtherPercent = parseFloat(selectedOption?.getAttribute('data-discount-other') || 0);

    let totalDiscountAmount = 0;

    cart.forEach(item => {
        const itemSubtotal = item.price * item.qty;
        const cat = (item.category || '').toLowerCase();

        let appliedPercent = discOtherPercent;
        if (cat.includes('biscuit') || cat.includes('cookie')) {
            appliedPercent = discBiscuitsPercent;
        }

        if (appliedPercent > 0) {
            totalDiscountAmount += (itemSubtotal * appliedPercent) / 100;
        }
    });

    const discountInput = document.getElementById('discountAmount');
    if (discountInput) {
        discountInput.value = totalDiscountAmount.toFixed(2);
    }

    updatePOSCalculations();
}

// Render Cart HTML & Totals
function renderCart() {
    const container = document.getElementById('cartItemsContainer');
    if (!container) return;

    const countBadge = document.getElementById('cartCountBadge');
    const totalCount = cart.reduce((sum, item) => sum + item.qty, 0);
    if (countBadge) {
        countBadge.innerText = totalCount + (totalCount === 1 ? ' item' : ' items');
    }

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fa-solid fa-basket-shopping fa-2x mb-2 text-secondary opacity-50"></i>
                <p class="mb-0 text-xs">Cart is currently empty.<br>Click products on the left to add items.</p>
            </div>`;
        document.getElementById('posSubtotal').innerText = 'Rs. 0.00';
        document.getElementById('posTotal').innerText = 'Rs. 0.00';
        if (document.getElementById('posChange')) {
            document.getElementById('posChange').innerText = 'Rs. 0.00';
        }
        document.getElementById('cartJsonInput').value = '';
        return;
    }

    let html = '<div class="d-flex flex-column">';

    cart.forEach(item => {
        const itemTotal = item.price * item.qty;

        html += `
            <div class="pos-cart-row py-2 px-1 border-bottom d-flex align-items-center justify-content-between gap-1">
                <!-- Item Name & Unit Price -->
                <div style="flex: 1; min-width: 0;" class="pe-1">
                    <div class="fw-bold text-dark text-break" style="font-size: 0.85rem; line-height: 1.25;" title="${item.name}">${item.name}</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Rs. ${item.price.toFixed(2)} each</small>
                </div>

                <!-- Qty Stepper -->
                <div style="width: 95px;" class="flex-shrink-0">
                    <div class="input-group input-group-sm">
                        <button type="button" class="btn btn-outline-secondary px-2 py-0" onclick="stepQty(${item.id}, -1)">-</button>
                        <input type="number" min="1" max="${item.stock || 9999}" id="cart-qty-${item.id}" value="${item.qty}" 
                               class="form-control form-control-sm text-center fw-bold px-1 py-0" 
                               style="height: 28px; font-size: 0.82rem;" 
                               oninput="setCartQty(${item.id}, this.value)" 
                               onclick="this.select()">
                        <button type="button" class="btn btn-outline-secondary px-2 py-0" onclick="stepQty(${item.id}, 1)">+</button>
                    </div>
                </div>

                <!-- Item Total -->
                <div style="width: 80px;" class="text-end flex-shrink-0">
                    <span id="cart-item-subtotal-${item.id}" class="fw-bold text-dark" style="font-size: 0.88rem;">Rs. ${itemTotal.toFixed(2)}</span>
                </div>

                <!-- Remove Button -->
                <div style="width: 24px;" class="text-end flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0" onclick="removeFromCart(${item.id})" title="Remove">
                        <i class="fa-solid fa-circle-xmark fa-lg"></i>
                    </button>
                </div>
            </div>`;
    });

    html += '</div>';
    container.innerHTML = html;

    document.getElementById('cartJsonInput').value = JSON.stringify(cart);

    // Recheck customer discount if active
    handleCustomerChange();
}

function updatePOSCalculations() {
    let subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    let discount = parseFloat(document.getElementById('discountAmount')?.value || 0);
    let total = Math.max(0, subtotal - discount);

    let paid = parseFloat(document.getElementById('paidAmount')?.value || 0);
    let change = Math.max(0, paid - total);

    document.getElementById('posSubtotal').innerText = 'Rs. ' + subtotal.toFixed(2);
    document.getElementById('posTotal').innerText = 'Rs. ' + total.toFixed(2);
    if (document.getElementById('posChange')) {
        document.getElementById('posChange').innerText = 'Rs. ' + change.toFixed(2);
    }
}

// Print Invoice Helper
function printReceiptModal() {
    window.print();
}
