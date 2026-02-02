<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout - GrabBaskets</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        /* PREMIUM ORANGE THEME */
        :root {
            --primary: #FF6B00;
            --primary-hover: #e65100;
            --primary-soft: #fff0e6;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --radius: 16px;
            --shadow-soft: 0 10px 30px -4px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 20px 40px -4px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-body);
            padding: 24px 16px;
            min-height: 100vh;
            color: var(--text-main);
        }

        .container {
            max-width: 600px;
            margin: auto;
        }

        /* HEADER */
        header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 16px 24px;
            border-radius: 50px;
            box-shadow: var(--shadow-hover);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: sticky;
            top: 0px;
            z-index: 9999;
            transition: all 0.3s ease;
        }

        .logo {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            gap: 8px;
            align-items: center;
            text-decoration: none;
        }

        .speed-badge {
            background: var(--primary-soft);
            padding: 6px 14px;
            border-radius: 20px;
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* CARDS */
        .section-box {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: var(--shadow-soft);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            color: var(--text-main);
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        h2 i {
            color: var(--primary);
        }

        /* ADDRESS */
        .delivery-address {
            background: #fff;
            border: 1px dashed var(--primary);
            padding: 24px;
            border-radius: 14px;
            margin-bottom: 16px;
            transition: .3s;
            cursor: pointer;
            position: relative;
            background: var(--primary-soft);
        }

        .delivery-address:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }

        .edit-btn {
            position: absolute;
            right: 16px;
            top: 16px;
            font-size: 12px;
            background: #fff;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            color: var(--primary);
            border: 1px solid transparent;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: 0.2s;
            cursor: pointer;
        }

        .edit-btn:hover {
            background: var(--primary);
            color: white;
        }

        .address-type {
            background: var(--primary);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* FORM FIELDS */
        .address-form input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border-color);
            margin-bottom: 12px;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            transition: 0.2s;
            background: #fcfcfc;
        }

        .address-form input:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px var(--primary-soft);
        }

        /* MAP & LOCATION BUTTONS */
        .location-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
        }

        .location-btn {
            flex: 1;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 12px;
            border: 1px dashed var(--border-color);
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .location-btn:hover {
            background: var(--primary-soft);
            color: var(--primary);
            border-color: var(--primary);
        }

        /* BUTTONS */
        .save-btn {
            background: var(--text-main);
            color: white;
            border: none;
            padding: 16px;
            width: 100%;
            border-radius: 14px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: 0.2s;
        }

        .save-btn:hover {
            background: black;
            transform: translateY(-1px);
        }

        .place-order-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary), #ff9100);
            color: #fff;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            margin-top: 10px;
            transition: all 0.3s;
            box-shadow: 0 8px 20px -4px rgba(255, 107, 0, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -4px rgba(255, 107, 0, 0.4);
        }

        /* PAGE 2 */
        #page2 {
            display: none;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-back-pill {
            margin-bottom: 20px;
            background: white;
            color: var(--text-main);
            border: 1px solid var(--border-color);
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .btn-back-pill:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* SUMMARY & ROWS */
        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            line-height: 1.6;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-main);
            min-width: 80px;
        }

        .info-value {
            color: var(--text-muted);
            text-align: right;
        }

        /* PAYMENT */
        .payment-option {
            padding: 18px;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            margin-bottom: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .payment-option:hover {
            border-color: #cbd5e1;
            background: #fcfcfc;
        }

        .payment-option.selected {
            border-color: var(--primary);
            background: var(--primary-soft);
            box-shadow: 0 0 0 1px var(--primary);
        }

        #selectedPayment {
            text-align: center;
            margin: 16px 0;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
        }

        /* UTILS */
        #mapPreview {
            height: 220px;
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            display: none;
            background: #eee;
        }

        .alert {
            background: #fffbeb;
            color: #d97706;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-weight: 600;
            border-left: 4px solid #f59e0b;
            font-size: 14px;
        }

        /* POPUP */
        #popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
            z-index: 999;
            padding: 20px;
        }

        .popup-box {
            background: white;
            padding: 40px;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 400px;
            width: 100%;
            origin: center;
            animation: pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes pop {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #22c55e;
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 32px;
            margin: 0 auto 24px;
            box-shadow: 0 10px 20px rgba(34, 197, 94, 0.3);
        }

        /* MOBILE RESPONSIVE TWEAKS */
        @media (max-width: 768px) {
            body {
                padding: 16px 12px;
                padding-bottom: 100px;
                /* Space for sticky bar */
            }

            .container {
                width: 100%;
            }

            header {
                padding: 12px 18px;
                top: 10px;
                margin-bottom: 20px;
            }

            .logo {
                font-size: 16px;
            }

            .speed-badge {
                font-size: 11px;
                padding: 4px 10px;
            }

            .section-box {
                padding: 20px;
                border-radius: 20px;
            }

            h2 {
                font-size: 16px;
                margin-bottom: 16px;
            }

            .location-buttons {
                flex-direction: column;
                gap: 8px;
            }

            .address-form input {
                padding: 12px 14px;
            }

            .info-row {
                font-size: 13px;
            }

            .payment-option {
                padding: 14px;
            }

            #mapPreview {
                height: 180px;
            }

            /* Sticky Bottom Button for Mobile */
            .place-order-btn {
                position: fixed;
                bottom: 16px;
                left: 16px;
                width: calc(100% - 32px);
                z-index: 1000;
                margin-top: 0;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                font-size: 16px;
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <a href="{{ route('customer.food.index') }}" class="logo">
                <i class="fa-solid fa-burger"></i> GrabBaskets
            </a>
            <div class="speed-badge"><i class="fa-solid fa-bolt"></i> Fast</div>
        </header>

        <div id="page1">
            <div class="section-box">
                <h2><i class="fa-solid fa-map-location-dot"></i> Delivery Address</h2>

                <!-- Address Form -->
                <div id="addressForm" class="address-form" style="display:none;">
                    <input id="inputName" placeholder="Full Name" value="{{ $customerName }}">
                    <input type="text" id="locationSearch" placeholder="Search area, street, etc..." />

                    <div class="location-buttons">
                        <button type="button" class="location-btn" onclick="useCurrentLocationForMap()">
                            <i class="fa-solid fa-crosshairs"></i> Use Current
                        </button>
                        <button type="button" class="location-btn" onclick="showMap()">
                            <i class="fa-solid fa-map"></i> Pick on Map
                        </button>
                    </div>

                    <div id="mapPreview">
                        <div id="googleMap" style="width:100%; height:100%;"></div>
                    </div>

                    <input id="inputAddress" placeholder="Flat / House / Building Address"
                        value="{{ $deliveryAddress }}">
                    <div style="display: flex; gap: 10px;">
                        <input id="inputPincode" placeholder="Pincode" value="600043">
                        <input id="inputLandmark" placeholder="Landmark" value="Near ABC School">
                    </div>
                    <input id="inputEmail" placeholder="Email Address" value="{{ $customerEmail }}">
                    <input id="inputPhone" placeholder="Phone Number" value="{{ $customerPhone }}">

                    <button class="save-btn" onclick="saveAddress()">Update Address</button>
                </div>

                <!-- Address Display -->
                <div id="addressDisplay" class="delivery-address">
                    <span class="address-type">HOME</span>
                    <button class="edit-btn" onclick="editAddress(event)"><i class="fa-solid fa-pen"></i> Edit</button>

                    <div id="addressText" style="margin-top:16px;">
                        <div class="info-row">
                            <div class="info-label">Name</div>
                            <div class="info-value" id="displayName">{{ $customerName }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Address</div>
                            <div class="info-value" id="displayAddress">{{ $deliveryAddress }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Pincode</div>
                            <div class="info-value">600043</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Landmark</div>
                            <div class="info-value">Near ABC School</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email</div>
                            <div class="info-value" id="displayEmail">{{ $customerEmail }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone</div>
                            <div class="info-value" id="displayPhone">{{ $customerPhone }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ORDER SUMMARY -->
            @foreach($sellerGroups as $group)
                <div class="section-box">
                    <h2><i class="fa-solid fa-store"></i>
                        {{ $group['hotel_name'] ?? $group['restaurant_name'] ?? 'Restaurant' }}</h2>

                    <div class="info-row"
                        style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px dashed var(--border-color);">
                        <div class="info-label">From</div>
                        <div>
                            <strong>{{ $group['hotel_name'] ?? $group['restaurant_name'] ?? 'Unknown Restaurant' }}</strong>
                        </div>
                    </div>

                    @foreach($group['items'] as $item)
                        <div class="info-row">
                            <div class="info-label" style="font-weight:400;">{{ $item->name }} <span
                                    style="font-size:12px; color:var(--text-muted);">x{{ $item->quantity }}</span></div>
                            <span style="font-weight:600;">₹{{ number_format($item->price * $item->quantity, 2) }}</span>
                        </div>
                    @endforeach

                    <div style="margin-top: 16px; border-top: 1px dashed var(--border-color); padding-top: 16px;">
                        <div class="info-row">
                            <span>Subtotal</span><span>₹{{ number_format($group['food_total'], 2) }}</span>
                        </div>
                        <div class="info-row"><span>Delivery Fee</span><span
                                style="color:var(--primary);">₹{{ number_format($group['delivery_fee'], 2) }}</span></div>
                        <div class="info-row"
                            style="font-weight:800; font-size:16px; margin-top:12px; color:var(--text-main);">
                            <span>Total Pay</span><span>₹{{ number_format($group['total'], 2) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach

            <button class="place-order-btn" onclick="goToPayment()">
                Continue to Payment <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <!-- PAGE 2: PAYMENT METHODS -->
        <div id="page2">
            <button class="btn-back-pill" onclick="goBack()">
                <i class="fa-solid fa-arrow-left"></i> Change Address
            </button>

            <div class="section-box">
                <h2><i class="fa-solid fa-wallet"></i> Select Payment Method</h2>

                <div class="payment-option" onclick="selectPayment(this, 'cod')">
                    <i class="fa-solid fa-money-bill-wave" style="color:var(--text-muted);"></i> Cash on Delivery
                </div>
                <div class="payment-option" onclick="selectPayment(this, 'upi')">
                    <i class="fa-solid fa-mobile-screen" style="color:var(--text-muted);"></i> UPI (GPay/PhonePe)
                </div>
                <div class="payment-option" onclick="selectPayment(this, 'card')">
                    <i class="fa-regular fa-credit-card" style="color:var(--text-muted);"></i> Credit / Debit Card
                </div>
            </div>

            @php
                $grandOriginalTotal = 0;
                $totalFoodTotal = 0;
                $totalDeliveryFee = 0;
                $totalTax = 0;
                foreach ($sellerGroups as $group) {
                    $totalFoodTotal += $group['food_total'];
                    $totalDeliveryFee += $group['delivery_fee'];
                    $totalTax += $group['tax'];
                    $grandOriginalTotal += $group['total'];
                }
            @endphp

            <div id="walletBenefitSection" style="display:none;" class="section-box"
                style="background: #f0fdf4; border: 2px solid var(--accent); margin-top: 24px;">
                <h2 style="color: var(--accent);"><i class="fa-solid fa-gift"></i> Wallet Benefit Applied</h2>
                <div class="info-row">
                    <span class="info-label" style="color: var(--accent);">Wallet Discount (15%)</span>
                    <span id="finalWalletDiscount" style="font-weight: 700; color: var(--accent);">-₹0</span>
                </div>
                <div class="text-end">
                    <small class="text-muted">Balance: ₹{{ number_format($walletPoint, 0) }}</small>
                </div>
            </div>

            <div id="walletDisabledSection" style="display:none;" class="section-box" style="margin-top: 24px;">
                <div class="alert"
                    style="background: #f8fafc; border: 1px dashed var(--border-color); color: var(--text-muted);">
                    <i class="fa-solid fa-circle-info"></i> Wallet discount not applied per your preference.
                </div>
            </div>

            <div class="section-box"
                style="background: var(--primary-soft); border: 2px solid var(--primary); margin-top: 24px;">
                <h2 style="color: var(--primary);"><i class="fa-solid fa-receipt"></i> Bill Detail</h2>

                <div class="info-row">
                    <span class="info-label">Item Total</span>
                    <span>₹{{ number_format($totalFoodTotal, 0) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Delivery Fee</span>
                    <span>₹{{ number_format($totalDeliveryFee, 0) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Taxes & Charges (5%)</span>
                    <span>₹{{ number_format($totalTax, 0) }}</span>
                </div>
                @if($walletPoint > 0)
                    <!-- Dynamic Wallet Row -->
                    <div id="dynamicWalletRow" class="info-row"
                        style="color: var(--accent); font-weight: 700; display: none;">
                        <span class="info-label" style="color: var(--accent);">Wallet Discount</span>
                        <span id="summaryWalletDiscount">-₹0</span>
                    </div>
                @endif

                <div class="info-row"
                    style="font-weight: 800; font-size: 18px; margin-top: 12px; color: var(--text-main); border-top: 1px dashed var(--primary); padding-top: 12px;">
                    <span>Total To Pay</span>
                    <span id="finalGrandTotal">₹{{ number_format($grandOriginalTotal, 0) }}</span>
                </div>
            </div>

            <p id="selectedPayment">Select a payment method above</p>

            <button class="place-order-btn" onclick="submitOrder()">
                Place Order <i class="fa-solid fa-check-circle"></i>
            </button>
        </div>
    </div>

    <!-- POPUP -->
    <div id="popup">
        <div class="popup-box">
            <div class="success-icon"><i class="fa-solid fa-check"></i></div>
            <h2 style="justify-content:center;">Order Placed!</h2>
            <p style="color:var(--text-muted); margin-bottom:24px;">Your food will arrive in 10 minutes! 🚀</p>
            <button class="place-order-btn" style="margin-top:0;" onclick="closePopup()">Track Status</button>
        </div>
    </div>

    <script>
        let selectedPaymentMethod = null;

        document.addEventListener('DOMContentLoaded', function () {
            updateBillDetail();
        });

        function updateBillDetail() {
            const useWallet = localStorage.getItem('food_use_wallet') !== 'false';
            const walletBalance = parseFloat("{{ $walletPoint }}");
            const originalGrandTotal = parseFloat("{{ $grandOriginalTotal }}");

            const benefitSection = document.getElementById('walletBenefitSection');
            const disabledSection = document.getElementById('walletDisabledSection');
            const dynamicRow = document.getElementById('dynamicWalletRow');
            const finalDiscountEl = document.getElementById('finalWalletDiscount');
            const summaryDiscountEl = document.getElementById('summaryWalletDiscount');
            const finalTotalEl = document.getElementById('finalGrandTotal');

            if (walletBalance > 0 && useWallet) {
                const discount = Math.round(Math.min(originalGrandTotal * 0.15, walletBalance));
                const finalTotal = originalGrandTotal - discount;

                if (benefitSection) benefitSection.style.display = 'block';
                if (disabledSection) disabledSection.style.display = 'none';
                if (dynamicRow) {
                    dynamicRow.style.display = 'flex';
                    summaryDiscountEl.textContent = '-₹' + discount.toFixed(0);
                }
                if (finalDiscountEl) finalDiscountEl.textContent = '-₹' + discount.toFixed(0);
                if (finalTotalEl) finalTotalEl.textContent = '₹' + finalTotal.toFixed(0);
            } else {
                if (benefitSection) benefitSection.style.display = 'none';
                if (walletBalance > 0) {
                    if (disabledSection) disabledSection.style.display = 'block';
                }
                if (dynamicRow) dynamicRow.style.display = 'none';
                if (finalTotalEl) finalTotalEl.textContent = '₹' + originalGrandTotal.toFixed(0);
            }
        }

        function saveAddress() {
            document.getElementById("displayName").innerText = document.getElementById("inputName").value;
            document.getElementById("displayAddress").innerText = document.getElementById("inputAddress").value;
            document.getElementById("displayEmail").innerText = document.getElementById("inputEmail").value;
            document.getElementById("displayPhone").innerText = document.getElementById("inputPhone").value;
            document.getElementById("addressForm").style.display = "none";
            document.getElementById("addressDisplay").style.display = "block";
        }

        function editAddress(event) {
            event.stopPropagation();
            document.getElementById("addressForm").style.display = "block";
            document.getElementById("addressDisplay").style.display = "none";
        }

        function goToPayment() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            document.getElementById("page1").style.display = "none";
            document.getElementById("page2").style.display = "block";
        }

        function goBack() {
            document.getElementById("page2").style.display = "none";
            document.getElementById("page1").style.display = "block";
        }

        function selectPayment(el, method) {
            document.querySelectorAll(".payment-option").forEach(e => e.classList.remove("selected"));
            el.classList.add("selected");
            selectedPaymentMethod = method;

            const label = document.getElementById('selectedPayment');
            let text = '';
            if (method === 'cod') text = 'Pay on Delivery';
            else if (method === 'upi') text = 'Pay via UPI';
            else if (method === 'card') text = 'Pay via Card';
            label.innerHTML = `<i class="fa-solid fa-circle-check" style="color:var(--primary)"></i> ${text} selected`;
        }

        function submitOrder() {
            if (typeof selectedPaymentMethod === 'undefined' || !selectedPaymentMethod) {
                alert("Please select a payment method.");
                return;
            }

            const useWallet = localStorage.getItem('food_use_wallet') !== 'false';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const formData = new FormData();

            formData.append('delivery_address', document.getElementById("displayAddress").innerText);
            formData.append('phone', document.getElementById("displayPhone").innerText);
            formData.append('email', document.getElementById("displayEmail").innerText);
            formData.append('payment_method', selectedPaymentMethod);
            formData.append('use_wallet', useWallet ? 'true' : 'false');
            formData.append('_token', csrfToken);

            // Show loading state
            const btns = document.querySelectorAll('.place-order-btn');
            const activeBtn = btns[btns.length - 1];
            const originalText = activeBtn.innerHTML;
            activeBtn.disabled = true;
            activeBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

            fetch("{{ route('customer.food.checkout.place') }}", {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.payment_required) {
                        // Initialize Razorpay
                        const options = {
                            "key": data.key,
                            "amount": data.amount,
                            "currency": "INR",
                            "name": "GrabBaskets Food",
                            "description": "Food Delivery Payment",
                            "order_id": data.razorpay_order_id,
                            "handler": function (response) {
                                // On Success Verify Payment
                                verifyPayment(response, data.razorpay_order_id);
                            },
                            "prefill": {
                                "name": data.customer.name,
                                "email": data.customer.email,
                                "contact": data.customer.contact
                            },
                            "theme": { "color": "#ff5200" },
                            "modal": {
                                "ondismiss": function () {
                                    activeBtn.disabled = false;
                                    activeBtn.innerHTML = originalText;
                                    alert("Payment cancelled. Please try again.");
                                }
                            }
                        };
                        const rzp1 = new Razorpay(options);
                        rzp1.open();
                    } else if (data.success) {
                        // COD Flow
                        document.getElementById("popup").style.display = "flex";
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 2000);
                    } else {
                        alert(data.message || "Failed to place order. Please try again.");
                        activeBtn.disabled = false;
                        activeBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Failed to place order. Please try again.");
                    activeBtn.disabled = false;
                    activeBtn.innerHTML = originalText;
                });
        }

        function verifyPayment(razorpayResponse, orderId) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const btns = document.querySelectorAll('.place-order-btn');
            const activeBtn = btns[btns.length - 1];

            activeBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Verifying Payment...';

            fetch("{{ route('customer.food.payment.verify') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    razorpay_payment_id: razorpayResponse.razorpay_payment_id,
                    razorpay_order_id: razorpayResponse.razorpay_order_id,
                    razorpay_signature: razorpayResponse.razorpay_signature
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById("popup").style.display = "flex";
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 2000);
                    } else {
                        alert(data.message || "Payment verification failed.");
                        activeBtn.disabled = false;
                        activeBtn.innerHTML = 'Place Order';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("An error occurred during verification.");
                    activeBtn.disabled = false;
                    activeBtn.innerHTML = 'Place Order';
                });
        }

        function closePopup() {
            document.getElementById("popup").style.display = "none";
        }

        function showMap() {
            document.getElementById("mapPreview").style.display = "block";
        }

        function useCurrentLocationForMap() {
            if (!navigator.geolocation) {
                alert("Geolocation not supported by your browser.");
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    alert("Location detected!");
                    document.getElementById("mapPreview").style.display = "block";
                },
                (error) => {
                    let msg = "Unable to get your location.";
                    if (error.code === 1) {
                        msg = "Please allow location access in your browser settings.";
                    }
                    alert(msg);
                }
            );
        }

        // Initialize page state
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById("addressForm").style.display = "none";
            document.getElementById("addressDisplay").style.display = "block";
        });
    </script>

    {{-- Google Maps API - Only load if enabled --}}
    @if(env('GOOGLE_MAPS_ENABLED', false) && env('GOOGLE_MAPS_API_KEY'))
        <script>
            const GOOGLE_MAPS_API_KEY = "{{ env('GOOGLE_MAPS_API_KEY') }}";
            if (GOOGLE_MAPS_API_KEY.trim()) {
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_API_KEY}&libraries=places`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }
        </script>
    @endif
</body>

</html>