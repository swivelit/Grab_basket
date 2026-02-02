<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GrabBaskets - Your Cart</title>

    <!-- UI Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        :root {
            /* Palette */
            --primary: #FF6B00;
            --primary-dark: #e65100;
            --primary-light: #fff0e6;
            --accent: #2ecc71;

            --bg-body: #f4f6f8;
            --bg-card: #ffffff;

            --text-main: #1e293b;
            --text-muted: #64748b;

            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);

            --radius-md: 0.75rem;
            --radius-lg: 1rem;

            --font-main: 'Plus Jakarta Sans', sans-serif;
            --font-heading: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            font-family: var(--font-main);
            color: var(--text-main);
            padding-bottom: 100px;
            /* Space for mobile sticky bar */
            -webkit-font-smoothing: antialiased;
        }

        /* Navbar Styling */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand-logo {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }

        .brand-logo i {
            font-size: 1.4rem;
        }

        .cart-badge-wrapper {
            position: relative;
            padding: 5px;
            transition: transform 0.2s;
        }

        .cart-badge-wrapper:hover {
            transform: scale(1.05);
        }

        /* Page Layout */
        .page-title {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1.8rem;
            margin: 2rem 0 1.5rem;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        /* Cart Items List */
        .cart-container {
            max-width: 1200px;
            /* Limit width for large screens */
            margin: 0 auto;
        }

        .cart-list-wrapper {
            background: transparent;
            border-radius: var(--radius-lg);
        }

        .cart-item {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: flex-start;
            /* Better alignment for varied content height */
        }

        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: #cbd5e1;
        }

        .item-img-wrapper {
            width: 120px;
            height: 120px;
            border-radius: var(--radius-md);
            overflow: hidden;
            flex-shrink: 0;
            background-color: #f1f5f9;
        }

        .item-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .cart-item:hover .item-img-wrapper img {
            transform: scale(1.05);
        }

        .item-content {
            flex-grow: 1;
            padding-left: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 120px;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .item-name {
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 1.15rem;
            color: var(--text-main);
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }

        .item-desc {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            max-width: 90%;
        }

        .item-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.25rem;
        }

        /* Quantity Control */
        .qty-controls {
            display: inline-flex;
            align-items: center;
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 50px;
            /* Pill shape */
            padding: 4px;
            gap: 2px;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #fff;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .qty-btn:hover {
            background: var(--primary);
            color: #fff;
        }

        .qty-btn:active {
            transform: scale(0.95);
        }

        .qty-val {
            font-weight: 600;
            min-width: 36px;
            text-align: center;
            font-size: 1rem;
            color: var(--text-main);
        }

        .remove-btn-icon {
            color: #ef4444;
            background: #fef2f2;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .remove-btn-icon:hover {
            background: #fee2e2;
            transform: scale(1.05);
        }

        /* Summary Panel */
        .summary-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: sticky;
            top: 100px;
        }

        .summary-title {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1.35rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px dashed var(--border-color);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .summary-row span:last-child {
            font-weight: 600;
            color: var(--text-main);
        }

        .summary-row.total {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            font-size: 1.25rem;
            color: var(--text-main);
        }

        .summary-row.total span:last-child {
            font-weight: 800;
            color: var(--primary);
        }

        .btn-checkout {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: var(--radius-md);
            padding: 1rem;
            width: 100%;
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 1.5rem;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(255, 107, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(255, 107, 0, 0.4);
            color: #fff;
        }

        .btn-continue {
            background: transparent;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 0.75rem;
            width: 100%;
            color: var(--text-muted);
            font-weight: 600;
            margin-top: 1rem;
            transition: all 0.2s;
        }

        .btn-continue:hover {
            border-color: var(--text-muted);
            color: var(--text-main);
            background: #f8fafc;
        }

        /* Empty State */
        .empty-cart-container {
            text-align: center;
            padding: 5rem 1rem;
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .empty-illustration {
            width: 150px;
            height: 150px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: var(--primary);
            font-size: 3.5rem;
        }

        .empty-title {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        /* Mobile Sticky Bar */
        .mobile-bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 1.5rem;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 1001;
            border-top: 1px solid var(--border-color);
        }

        @media (max-width: 991px) {
            .cart-item {
                padding: 1rem;
            }

            .item-img-wrapper {
                width: 90px;
                height: 90px;
            }

            .item-content {
                padding-left: 1rem;
                min-height: 90px;
            }

            .item-name {
                font-size: 1rem;
            }

            .item-desc {
                display: none;
                /* Hide Description on small screens */
            }

            .remove-btn-icon {
                width: 32px;
                height: 32px;
            }

            .remove-label {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar-custom">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ route('customer.food.index') }}" class="brand-logo">
                <i class="fa-solid fa-burger"></i> GrabBaskets
            </a>
            <div class="cart-badge-wrapper">
                <a href="#" class="text-dark fs-4 position-relative" id="cartBtn" title="Your Cart">
                    <i class="fa-solid fa-bag-shopping" style="color: var(--text-main);"></i>
                    <span id="cartCount"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white"
                        style="font-size: 0.65rem; padding: 0.35em 0.65em;">0</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="container cart-container">
        <h2 class="page-title">Your Food Cart</h2>

        <div class="row g-4">
            <!-- Left: Cart Items -->
            <div class="col-lg-8" id="cartItemsSection">
                <div class="cart-list-wrapper" id="cartList">
                    <div id="itemsContainer">
                        <!-- Dynamic items here -->
                    </div>
                </div>

                <div id="emptyState" class="empty-cart-container" style="display:none;">
                    <div class="empty-illustration">
                        <i class="fa-solid fa-basket-shopping"></i>
                    </div>
                    <h3 class="empty-title">Your cart is empty</h3>
                    <p class="text-muted mb-4">Looks like you haven't indulged in anything yet.</p>
                    <a href="{{ route('customer.food.index') }}"
                        class="btn btn-primary rounded-pill px-5 py-2 fw-semibold"
                        style="background: var(--primary); border: none;">
                        Internal Menu
                    </a>
                </div>
            </div>

            <!-- Right: Order Summary -->
            <div class="col-lg-4 d-none d-lg-block" id="summarySection">
                <div class="summary-card">
                    <h5 class="summary-title">Order Summary</h5>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal">₹0</span>
                    </div>
                    <div id="walletSection" style="display:none;" class="card bg-light border-0 mb-3">
                        <div class="card-body p-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="useWalletCheckbox"
                                    onchange="toggleWallet(this.checked)">
                                <label class="form-check-label fw-bold text-dark" for="useWalletCheckbox">Use Wallet
                                    (Save 15%)</label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Available: ₹{{ $walletPoint }}</span>
                                <span id="walletDiscount" class="fw-bold" style="color: var(--accent);">-₹0</span>
                            </div>
                        </div>
                    </div>
                    <div id="noWalletMsg" style="display:none;" class="alert alert-warning py-2 mb-3">
                        <small><i class="fa-solid fa-circle-info"></i> No amount in your wallet to apply
                            discount.</small>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <span id="delivery">₹50</span>
                    </div>
                    <div class="summary-row">
                        <span>Taxes & Charges (5%)</span>
                        <span id="tax">₹0</span>
                    </div>
                    <div class="summary-row total">
                        <span>Grand Total</span>
                        <span id="total">₹0</span>
                    </div>

                    <button class="btn btn-checkout" onclick="checkout()">
                        <span>Proceed to Pay</span>
                        <i class="fa-solid fa-arrow-right-long"></i>
                    </button>
                    <button class="btn btn-continue" onclick="continueShopping()">
                        Add More Items
                    </button>

                    <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-center text-muted gap-2">
                        <i class="fa-solid fa-lock" style="color: var(--accent);"></i>
                        <small>100% Secure Checkout</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Sticky Bottom Bar -->
    <div class="mobile-bottom-bar d-lg-none" id="mobileBottomBar">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div class="d-flex flex-column">
                <span class="text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total</span>
                <span class="fw-bold fs-4" id="mobileTotal" style="color: var(--primary);">₹0</span>
            </div>
            <button class="btn btn-checkout py-2 px-4 mt-0 w-auto shadow-none" onclick="checkout()">
                Checkout
            </button>
        </div>
        <div class="text-center mt-2">
            <small class="text-muted" style="font-size: 0.7rem;" onclick="continueShopping()">continue shopping</small>
        </div>
    </div>

    <!-- DATA FOR JS -->
    <div id="products-data" style="display:none">@json($foodsForJs)</div>
    <div id="cart-data" style="display:none">@json(array_values($cartData))</div>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuration
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const CHECKOUT_URL = "{{ route('customer.food.checkout') }}";
        const INDEX_URL = "{{ route('customer.food.index') }}";
        const UPDATE_URL_TEMPLATE = "{{ route('customer.food.cart.update', ['foodId' => 'ID']) }}";
        const REMOVE_URL_TEMPLATE = "{{ route('customer.food.cart.remove', ['foodId' => 'ID']) }}";

        // State
        const PRODUCTS = JSON.parse(document.getElementById('products-data').textContent);
        const INITIAL_CART = JSON.parse(document.getElementById('cart-data').textContent);
        let cart = INITIAL_CART.map(item => ({
            id: item.id,
            qty: item.quantity
        }));

        // DOM Elements
        const itemsContainer = document.getElementById('itemsContainer');
        const subtotalEl = document.getElementById('subtotal');
        const deliveryEl = document.getElementById('delivery');
        const taxEl = document.getElementById('tax');
        const totalEl = document.getElementById('total');
        const mobileTotalEl = document.getElementById('mobileTotal');
        const cartCountEl = document.getElementById('cartCount');
        const emptyState = document.getElementById('emptyState');
        const summarySection = document.getElementById('summarySection');
        const cartList = document.getElementById('cartList');
        const mobileBottomBar = document.getElementById('mobileBottomBar');

        function findProduct(id) {
            return PRODUCTS.find(p => p.id === id);
        }

        function renderCart() {
            itemsContainer.innerHTML = '';

            if (cart.length === 0) {
                emptyState.style.display = 'block';
                cartList.style.display = 'none';
                summarySection.classList.add('d-none');
                mobileBottomBar.style.display = 'none';
                cartCountEl.textContent = '0';
                return;
            }

            emptyState.style.display = 'none';
            cartList.style.display = 'block';
            summarySection.classList.remove('d-none');

            // Mobile bar visibility logic
            if (window.innerWidth < 992) {
                mobileBottomBar.style.display = 'block';
            } else {
                mobileBottomBar.style.display = 'none';
            }

            let subtotal = 0;
            cart.forEach(entry => {
                const prod = findProduct(entry.id);
                if (!prod) return;

                const itemTotal = prod.price * entry.qty;
                subtotal += itemTotal;

                const itemHtml = `
                    <div class="cart-item" id="item-row-${prod.id}">
                        <div class="item-img-wrapper">
                            <img src="${prod.img}" alt="${prod.name}">
                        </div>
                        <div class="item-content">
                            <div class="d-flex justify-content-between align-items-start w-100">
                                <div class="w-100">
                                    <div class="d-flex justify-content-between">
                                        <h4 class="item-name">${prod.name}</h4>
                                        <div class="item-price">₹${prod.price.toFixed(0)}</div>
                                    </div>
                                    <p class="item-desc">${prod.desc}</p>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2">
                                <div class="qty-controls">
                                    <button class="qty-btn" onclick="changeQty(${prod.id}, -1)">
                                        <i class="fa-solid fa-minus" style="font-size: 0.7em;"></i>
                                    </button>
                                    <span class="qty-val" id="qty-${prod.id}">${entry.qty}</span>
                                    <button class="qty-btn" onclick="changeQty(${prod.id}, 1)">
                                        <i class="fa-solid fa-plus" style="font-size: 0.7em;"></i>
                                    </button>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fw-bold fs-6 d-none d-sm-block">Total: ₹${itemTotal.toFixed(0)}</span>
                                    <button class="remove-btn-icon" onclick="removeItem(${prod.id})" title="Remove">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                itemsContainer.insertAdjacentHTML('beforeend', itemHtml);
            });

            const delivery = 50;
            const tax = Math.round(subtotal * 0.05);
            const rawGrand = subtotal + delivery + tax;

            // Wallet Discount 15%
            const walletBalance = parseFloat("{{ $walletPoint }}");
            let walletDiscount = 0;
            const walletSection = document.getElementById('walletSection');
            const noWalletMsg = document.getElementById('noWalletMsg');
            const walletCheckbox = document.getElementById('useWalletCheckbox');

            if (walletBalance > 0) {
                walletSection.style.display = 'block';
                noWalletMsg.style.display = 'none';

                // Check localStorage for preference, default to true
                const useWalletPref = localStorage.getItem('food_use_wallet') !== 'false';
                walletCheckbox.checked = useWalletPref;

                if (useWalletPref) {
                    walletDiscount = Math.min(rawGrand * 0.15, walletBalance);
                    document.getElementById('walletDiscount').textContent = '-₹' + walletDiscount.toFixed(0);
                    document.getElementById('walletDiscount').style.opacity = '1';
                } else {
                    document.getElementById('walletDiscount').textContent = '₹0';
                    document.getElementById('walletDiscount').style.opacity = '0.5';
                }
            } else {
                walletSection.style.display = 'none';
                noWalletMsg.style.display = 'block';
                localStorage.setItem('food_use_wallet', 'false');
            }

            const grand = rawGrand - walletDiscount;

            subtotalEl.textContent = '₹' + subtotal.toFixed(0);
            deliveryEl.textContent = '₹' + delivery;
            taxEl.textContent = '₹' + tax;
            totalEl.textContent = '₹' + grand.toFixed(0);
            mobileTotalEl.textContent = '₹' + grand.toFixed(0);

            const count = cart.reduce((s, c) => s + c.qty, 0);
            cartCountEl.textContent = count;
        }

        function toggleWallet(checked) {
            localStorage.setItem('food_use_wallet', checked);
            renderCart();
        }

        async function changeQty(id, delta) {
            const entry = cart.find(c => c.id === id);
            if (!entry) return;

            const newQty = entry.qty + delta;
            if (newQty < 1) {
                removeItem(id);
                return;
            }

            // Optimistic Update
            const oldQty = entry.qty;
            entry.qty = newQty;
            renderCart();

            const url = UPDATE_URL_TEMPLATE.replace('ID', id);
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify({
                        quantity: newQty
                    })
                });

                if (!response.ok) {
                    // Revert if failed
                    entry.qty = oldQty;
                    renderCart();
                    showToast('Failed to update cart', 'danger');
                }
            } catch (e) {
                console.error('Error:', e);
                entry.qty = oldQty;
                renderCart();
                showToast('Network error', 'danger');
            }
        }

        async function removeItem(id) {
            if (!confirm('Are you sure you want to remove this item?')) return;

            // Optimistic UI Removal
            const row = document.getElementById(`item-row-${id}`);
            if (row) {
                row.style.opacity = '0.5';
                row.style.pointerEvents = 'none';
            }

            const url = REMOVE_URL_TEMPLATE.replace('ID', id);
            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    }
                });

                if (response.ok) {
                    cart = cart.filter(c => c.id !== id);
                    if (row) {
                        row.style.transform = 'scale(0.95)';
                        row.style.opacity = '0';
                        setTimeout(() => renderCart(), 200);
                    } else {
                        renderCart();
                    }
                } else {
                    if (row) {
                        row.style.opacity = '1';
                        row.style.pointerEvents = 'auto';
                    }
                    showToast('Failed to remove item', 'danger');
                }
            } catch (e) {
                if (row) {
                    row.style.opacity = '1';
                    row.style.pointerEvents = 'auto';
                }
                console.error('Error:', e);
            }
        }

        function checkout() {
            if (cart.length === 0) return;
            // Add loading effect
            const buttons = document.querySelectorAll('.btn-checkout');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
            });
            window.location.href = CHECKOUT_URL;
        }

        function continueShopping() {
            window.location.href = INDEX_URL;
        }

        function showToast(msg, type = 'dark') {
            // Simple robust alert for now, can be upgraded to bootstrap toast
            alert(msg);
        }

        window.addEventListener('resize', () => {
            if (cart.length > 0) {
                const isMobile = window.innerWidth < 992;
                mobileBottomBar.style.display = isMobile ? 'block' : 'none';
            }
        });

        // Initial Render
        renderCart();
    </script>

</body>

</html>