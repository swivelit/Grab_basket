@extends('layouts.app')

@section('title', 'Checkout - GrabBaskets')

@section('head')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=places" async defer></script>
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  :root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    
    --zepto-green: #0C831F;
    --express-red: #FF3B3B;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.16);
    --shadow-xl: 0 12px 48px rgba(0,0,0,0.20);
  }

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    padding-bottom: 50px;
  }

  .checkout-wrapper {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 300px;
    padding: 40px 0 120px 0;
    position: relative;
    overflow: hidden;
  }

  .checkout-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
  }

  .checkout-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
    z-index: 1;
  }

  .checkout-hero {
    text-align: center;
    color: white;
    margin-bottom: -80px;
  }

  .checkout-hero h1 {
    font-size: 3.5rem;
    font-weight: 900;
    margin-bottom: 12px;
    text-shadow: 0 4px 12px rgba(0,0,0,0.2);
    letter-spacing: -1px;
  }

  .checkout-hero p {
    font-size: 1.25rem;
    opacity: 0.95;
    font-weight: 500;
  }

  .stats-row {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-top: 30px;
  }

  .stat-card {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    padding: 20px 40px;
    border-radius: 20px;
    border: 2px solid rgba(255,255,255,0.2);
  }

  .stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 8px;
  }

  .stat-value {
    font-size: 2.5rem;
    font-weight: 900;
    line-height: 1;
  }

  .checkout-grid {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 30px;
    margin-top: 100px;
  }

  .glass-card {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 40px;
    box-shadow: var(--shadow-xl);
    margin-bottom: 24px;
    border: 1px solid rgba(255,255,255,0.8);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  }

  .glass-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 64px rgba(0,0,0,0.25);
  }

  .card-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 3px solid #f0f0f0;
    position: relative;
  }

  .card-header::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    width: 80px;
    height: 3px;
    background: var(--primary-gradient);
    border-radius: 3px;
  }

  .card-icon {
    width: 64px;
    height: 64px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: hidden;
  }

  .card-icon::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
    transform: rotate(45deg);
    animation: shine 3s infinite;
  }

  @keyframes shine {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
  }

  .card-title {
    flex: 1;
  }

  .card-title h4 {
    font-size: 1.5rem;
    font-weight: 800;
    margin-bottom: 4px;
    color: #2d3436;
  }

  .card-title small {
    color: #636e72;
    font-size: 0.95rem;
    font-weight: 500;
  }

  .delivery-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
  }

  .delivery-card {
    position: relative;
    border: 3px solid #e0e0e0;
    border-radius: 20px;
    padding: 28px;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    overflow: hidden;
    background: white;
  }

  .delivery-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    opacity: 0;
    transition: opacity 0.4s;
  }

  .delivery-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: var(--shadow-lg);
  }

  .delivery-card:hover::before {
    opacity: 1;
  }

  .delivery-card.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
  }

  .delivery-card.express-card {
    border-color: #ff6b6b;
  }

  .delivery-card.express-card.selected {
    border-color: #ff6b6b;
    background: linear-gradient(135deg, rgba(255, 107, 107, 0.08), rgba(238, 90, 111, 0.08));
    box-shadow: 0 8px 32px rgba(255, 107, 107, 0.3);
  }

  .delivery-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: var(--shadow-sm);
  }

  .badge-express {
    background: var(--danger-gradient);
    color: white;
    animation: pulse-glow 2s infinite;
  }

  .badge-standard {
    background: var(--info-gradient);
    color: white;
  }

  @keyframes pulse-glow {
    0%, 100% {
      box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7);
    }
    50% {
      box-shadow: 0 0 0 8px rgba(255, 107, 107, 0);
    }
  }

  .delivery-icon {
    font-size: 3.5rem;
    margin-bottom: 16px;
    display: block;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
  }

  .delivery-title {
    font-size: 1.25rem;
    font-weight: 800;
    margin-bottom: 8px;
    color: #2d3436;
  }

  .delivery-desc {
    color: #636e72;
    font-size: 0.9rem;
    margin-bottom: 16px;
  }

  .delivery-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 2px solid #f0f0f0;
  }

  .delivery-count {
    font-size: 1.1rem;
    font-weight: 800;
  }

  .delivery-amount {
    font-size: 0.9rem;
    color: #636e72;
    margin-top: 4px;
  }

  .form-group {
    margin-bottom: 24px;
  }

  .form-label {
    display: block;
    font-weight: 700;
    margin-bottom: 10px;
    color: #2d3436;
    font-size: 0.95rem;
  }

  .form-control {
    width: 100%;
    padding: 16px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 14px;
    font-size: 1rem;
    transition: all 0.3s;
    background: white;
    font-family: inherit;
  }

  .form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    background: #fafbfc;
  }

  textarea.form-control {
    resize: vertical;
    min-height: 80px;
  }

  .location-button {
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 16px 32px;
    border-radius: 14px;
    cursor: pointer;
    font-weight: 700;
    font-size: 1rem;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow-md);
  }

  .location-button:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
  }

  .location-button:active {
    transform: translateY(-1px);
  }

  .eligibility-badge {
    padding: 16px 24px;
    border-radius: 14px;
    font-weight: 700;
    margin-top: 20px;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    font-size: 1rem;
    box-shadow: var(--shadow-sm);
  }

  .eligible {
    background: var(--success-gradient);
    color: white;
  }

  .not-eligible {
    background: var(--warning-gradient);
    color: white;
  }

  #map {
    height: 350px;
    width: 100%;
    border-radius: 16px;
    margin-top: 20px;
    box-shadow: var(--shadow-md);
    border: 3px solid #e0e0e0;
  }

  .cart-items-wrapper {
    max-height: 450px;
    overflow-y: auto;
    padding-right: 8px;
  }

  .cart-items-wrapper::-webkit-scrollbar {
    width: 8px;
  }

  .cart-items-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }

  .cart-items-wrapper::-webkit-scrollbar-thumb {
    background: var(--primary-gradient);
    border-radius: 10px;
  }

  .section-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 800;
    font-size: 0.9rem;
    margin-bottom: 20px;
    box-shadow: var(--shadow-sm);
  }

  .badge-express-section {
    background: var(--danger-gradient);
    color: white;
  }

  .badge-standard-section {
    background: var(--info-gradient);
    color: white;
  }

  .cart-item {
    display: flex;
    gap: 20px;
    padding: 20px;
    border-radius: 16px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    margin-bottom: 16px;
    transition: all 0.3s;
    border: 2px solid transparent;
  }

  .cart-item:hover {
    transform: translateX(8px);
    box-shadow: var(--shadow-md);
    border-color: #667eea;
  }

  .cart-item-image {
    width: 90px;
    height: 90px;
    border-radius: 14px;
    object-fit: cover;
    box-shadow: var(--shadow-sm);
    border: 3px solid white;
  }

  .cart-item-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .cart-item-name {
    font-weight: 700;
    font-size: 1.05rem;
    color: #2d3436;
    margin-bottom: 8px;
  }

  .cart-item-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .cart-item-qty {
    color: #636e72;
    font-weight: 600;
  }

  .cart-item-price {
    font-weight: 800;
    font-size: 1.15rem;
    color: #2d3436;
  }

  .item-delivery-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 700;
    margin-top: 8px;
  }

  .tag-express {
    background: rgba(255, 107, 107, 0.15);
    color: #d63031;
  }

  .tag-standard {
    background: rgba(79, 172, 254, 0.15);
    color: #0984e3;
  }

  .payment-option {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border: 3px solid #e0e0e0;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .payment-option:hover {
    border-color: #667eea;
    box-shadow: var(--shadow-md);
  }

  .payment-option.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
  }

  .payment-radio {
    width: 24px;
    height: 24px;
    accent-color: #667eea;
  }

  .payment-label {
    font-weight: 700;
    font-size: 1rem;
    color: #2d3436;
    cursor: pointer;
  }

  .order-summary-card {
    position: sticky;
    top: 20px;
    background: linear-gradient(135deg, #2d3436 0%, #34495e 100%);
    color: white;
    border-radius: 24px;
    padding: 40px;
    box-shadow: var(--shadow-xl);
  }

  .summary-title {
    font-size: 1.75rem;
    font-weight: 900;
    margin-bottom: 32px;
    text-align: center;
  }

  .summary-row {
    display: flex;
    justify-content: space-between;
    padding: 16px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    font-size: 1rem;
  }

  .summary-label {
    font-weight: 600;
    opacity: 0.9;
  }

  .summary-value {
    font-weight: 800;
  }

  .summary-total {
    border-top: 3px solid rgba(255,255,255,0.2);
    border-bottom: none;
    padding-top: 24px;
    margin-top: 16px;
    font-size: 1.5rem;
  }

  .summary-total .summary-value {
    background: var(--success-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .checkout-button {
    width: 100%;
    padding: 20px;
    border: none;
    border-radius: 16px;
    background: var(--success-gradient);
    color: white;
    font-size: 1.2rem;
    font-weight: 900;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 8px 32px rgba(17, 153, 142, 0.4);
    margin-top: 28px;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  .checkout-button:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 48px rgba(17, 153, 142, 0.5);
  }

  .checkout-button:active {
    transform: translateY(-2px);
  }

  .security-badge {
    text-align: center;
    margin-top: 20px;
    padding: 16px;
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
  }

  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.95), rgba(118, 75, 162, 0.95));
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    backdrop-filter: blur(10px);
  }

  .loading-overlay.active {
    display: flex;
  }

  .loading-content {
    text-align: center;
    color: white;
  }

  .spinner-container {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 30px;
  }

  .spinner {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 6px solid rgba(255, 255, 255, 0.2);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  .spinner:nth-child(2) {
    width: 80%;
    height: 80%;
    top: 10%;
    left: 10%;
    border-top-color: rgba(255, 255, 255, 0.8);
    animation-duration: 0.7s;
    animation-direction: reverse;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .loading-text {
    font-size: 1.5rem;
    font-weight: 800;
    margin-bottom: 12px;
  }

  .loading-subtext {
    font-size: 1rem;
    opacity: 0.9;
  }

  @media (max-width: 1024px) {
    .checkout-grid {
      grid-template-columns: 1fr;
      margin-top: 40px;
    }

    .delivery-selector {
      grid-template-columns: 1fr;
    }

    .order-summary-card {
      position: relative;
      top: 0;
    }

    .checkout-hero h1 {
      font-size: 2.5rem;
    }

    .stats-row {
      flex-direction: column;
      gap: 12px;
    }

    .stat-card {
      padding: 16px 30px;
    }
  }

  @media (max-width: 768px) {
    .checkout-hero h1 {
      font-size: 2rem;
    }

    .glass-card {
      padding: 24px;
    }

    .card-icon {
      width: 48px;
      height: 48px;
      font-size: 1.5rem;
    }

    #map {
      height: 250px;
    }
  }
</style>
@endsection

@section('content')
<div class="checkout-wrapper">
  <div class="checkout-container">
    <!-- Hero Section -->
    <div class="checkout-hero">
      <h1>🎯 Secure Checkout</h1>
      <p>Complete your order with confidence</p>
      
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Total Items</div>
          <div class="stat-value">{{ $expressCartItems->count() + $standardCartItems->count() }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Order Value</div>
          <div class="stat-value">₹{{ number_format(($expressTotal + $standardTotal) * 1.18, 0) }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="checkout-container">
  <form id="checkout-form" method="POST" action="{{ route('cart.checkout') }}">
    @csrf
    
    <div class="checkout-grid">
      <!-- Left Column - Main Content -->
      <div>
        <!-- Delivery Type Selection -->
        <div class="glass-card">
          <div class="card-header">
            <div class="card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
              ⚡
            </div>
            <div class="card-title">
              <h4>Choose Delivery Speed</h4>
              <small>Select how fast you want your order</small>
            </div>
          </div>

          <div class="delivery-selector">
            <!-- Express 10-Min Delivery -->
            <div class="delivery-card express-card" id="express-option" onclick="selectDeliveryType('express')">
              <div class="delivery-badge badge-express">FASTEST</div>
              <span class="delivery-icon">🚀</span>
              <h5 class="delivery-title">10-Minute Express</h5>
              <p class="delivery-desc">Lightning fast delivery to your door</p>
              <div class="delivery-stats">
                <div>
                  <div class="delivery-count" style="color: #ff6b6b;">
                    {{ $expressCartItems->count() }} items
                  </div>
                  <div class="delivery-amount">
                    @if($expressCartItems->count() > 0)
                      ₹{{ number_format($expressTotal, 2) }}
                    @else
                      No express items
                    @endif
                  </div>
                </div>
                <div style="font-size: 2rem;">🔥</div>
              </div>
            </div>

            <!-- Standard Delivery -->
            <div class="delivery-card" id="standard-option" onclick="selectDeliveryType('standard')">
              <div class="delivery-badge badge-standard">STANDARD</div>
              <span class="delivery-icon">🚚</span>
              <h5 class="delivery-title">Standard Delivery</h5>
              <p class="delivery-desc">Reliable 1-2 days delivery</p>
              <div class="delivery-stats">
                <div>
                  <div class="delivery-count" style="color: #4facfe;">
                    {{ $standardCartItems->count() }} items
                  </div>
                  <div class="delivery-amount">
                    @if($standardCartItems->count() > 0)
                      ₹{{ number_format($standardTotal, 2) }}
                    @else
                      No standard items
                    @endif
                  </div>
                </div>
                <div style="font-size: 2rem;">📦</div>
              </div>
            </div>
          </div>

          <input type="hidden" name="delivery_type" id="delivery-type-input" value="express">
        </div>

        <!-- Delivery Address with Google Maps -->
        <div class="glass-card">
          <div class="card-header">
            <div class="card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
              📍
            </div>
            <div class="card-title">
              <h4>Delivery Address</h4>
              <small>Where should we deliver your order?</small>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Street Address</label>
            <textarea name="address" id="address-input" class="form-control" rows="2" required 
                      placeholder="House/Flat no, Street name, Area, Landmark">{{ old('address', auth()->user()->address ?? '') }}</textarea>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="city" id="city-input" class="form-control" required 
                       value="{{ old('city', auth()->user()->city ?? '') }}" placeholder="Enter your city">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">State</label>
                <input type="text" name="state" id="state-input" class="form-control" required 
                       value="{{ old('state', auth()->user()->state ?? '') }}" placeholder="Enter your state">
              </div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Pincode</label>
                <input type="text" name="pincode" id="pincode-input" class="form-control" required 
                       value="{{ old('pincode', auth()->user()->pincode ?? '') }}" 
                       placeholder="6-digit pincode" pattern="[0-9]{6}">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control" required 
                       value="{{ old('phone', auth()->user()->phone ?? '') }}" 
                       placeholder="10-digit mobile" pattern="[0-9]{10}">
              </div>
            </div>
          </div>

          <button type="button" class="location-button" onclick="detectLocation()">
            <i class="bi bi-crosshair"></i>
            Use My Current Location
          </button>

          <div id="eligibility-status"></div>

          <!-- Google Map -->
          <div id="map"></div>

          <input type="hidden" name="latitude" id="latitude-input">
          <input type="hidden" name="longitude" id="longitude-input">
        </div>

        <!-- Cart Items Preview -->
        <div class="glass-card">
          <div class="card-header">
            <div class="card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
              🛍️
            </div>
            <div class="card-title">
              <h4>Your Order Items</h4>
              <small>{{ $expressCartItems->count() + $standardCartItems->count() }} items ready for checkout</small>
            </div>
          </div>

          <div class="cart-items-wrapper">
            @if($expressCartItems->count() > 0)
              <span class="section-badge badge-express-section">
                <i class="bi bi-lightning-fill"></i>
                Express Delivery (10 mins)
              </span>
              @foreach($expressCartItems as $item)
                <div class="cart-item">
                  <img src="{{ $item->product->image ?? '/images/placeholder.png' }}" 
                       alt="{{ $item->product->name }}" class="cart-item-image">
                  <div class="cart-item-info">
                    <div class="cart-item-name">{{ $item->product->name }}</div>
                    <div class="cart-item-details">
                      <span class="cart-item-qty">Qty: {{ $item->quantity }}</span>
                      <strong class="cart-item-price">₹{{ number_format($item->product->price * $item->quantity, 2) }}</strong>
                    </div>
                    <span class="item-delivery-tag tag-express">
                      <i class="bi bi-lightning-fill"></i> 10-Min Express
                    </span>
                  </div>
                </div>
              @endforeach
            @endif

            @if($standardCartItems->count() > 0)
              <span class="section-badge badge-standard-section" style="margin-top: 20px;">
                <i class="bi bi-box-seam"></i>
                Standard Delivery (1-2 days)
              </span>
              @foreach($standardCartItems as $item)
                <div class="cart-item">
                  <img src="{{ $item->product->image ?? '/images/placeholder.png' }}" 
                       alt="{{ $item->product->name }}" class="cart-item-image">
                  <div class="cart-item-info">
                    <div class="cart-item-name">{{ $item->product->name }}</div>
                    <div class="cart-item-details">
                      <span class="cart-item-qty">Qty: {{ $item->quantity }}</span>
                      <strong class="cart-item-price">₹{{ number_format($item->product->price * $item->quantity, 2) }}</strong>
                    </div>
                    <span class="item-delivery-tag tag-standard">
                      <i class="bi bi-box-seam"></i> Standard Delivery
                    </span>
                  </div>
                </div>
              @endforeach
            @endif
          </div>
        </div>

        <!-- Payment Method -->
        <div class="glass-card">
          <div class="card-header">
            <div class="card-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
              💳
            </div>
            <div class="card-title">
              <h4>Payment Method</h4>
              <small>Choose your preferred payment option</small>
            </div>
          </div>

          <div class="payment-option" onclick="selectPayment('razorpay')">
            <input class="payment-radio" type="radio" name="payment_method" id="razorpay" value="razorpay" checked>
            <label class="payment-label" for="razorpay">
              💳 Razorpay (Cards • UPI • Wallets • Net Banking)
            </label>
          </div>

          <div class="payment-option" onclick="selectPayment('cod')">
            <input class="payment-radio" type="radio" name="payment_method" id="cod" value="cod">
            <label class="payment-label" for="cod">
              💵 Cash on Delivery (COD)
            </label>
          </div>
        </div>
      </div>

      <!-- Right Column - Order Summary -->
      <div>
        <div class="order-summary-card">
          <h3 class="summary-title">📊 Order Summary</h3>

          <div class="summary-row">
            <span class="summary-label">Express Items ({{ $expressCartItems->count() }})</span>
            <strong class="summary-value">₹{{ number_format($expressTotal, 2) }}</strong>
          </div>

          <div class="summary-row">
            <span class="summary-label">Standard Items ({{ $standardCartItems->count() }})</span>
            <strong class="summary-value">₹{{ number_format($standardTotal, 2) }}</strong>
          </div>

          <div class="summary-row">
            <span class="summary-label">Delivery Charges</span>
            <strong class="summary-value" style="color: #38ef7d;">FREE 🎉</strong>
          </div>

          <div class="summary-row">
            <span class="summary-label">Taxes & Fees (18%)</span>
            <strong class="summary-value">₹{{ number_format(($expressTotal + $standardTotal) * 0.18, 2) }}</strong>
          </div>

          <div class="summary-row summary-total">
            <span class="summary-label">Total Amount</span>
            <strong class="summary-value">₹{{ number_format(($expressTotal + $standardTotal) * 1.18, 2) }}</strong>
          </div>

          <button type="submit" class="checkout-button">
            <i class="bi bi-shield-check-fill"></i> Place Secure Order
          </button>

          <div class="security-badge">
            <i class="bi bi-lock-fill"></i> SSL Encrypted Payment
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loading-overlay">
  <div class="loading-content">
    <div class="spinner-container">
      <div class="spinner"></div>
      <div class="spinner"></div>
    </div>
    <div class="loading-text">Processing Your Order</div>
    <div class="loading-subtext">Please wait while we prepare your checkout...</div>
  </div>
</div>

<script>
  let map;
  let marker;
  let selectedDeliveryType = 'express';

  // Initialize Google Map
  function initMap() {
    const defaultLocation = { lat: 12.9716, lng: 77.5946 }; // Bangalore
    
    map = new google.maps.Map(document.getElementById('map'), {
      zoom: 14,
      center: defaultLocation,
      styles: [
        {
          featureType: 'poi',
          elementType: 'labels',
          stylers: [{ visibility: 'off' }]
        },
        {
          featureType: 'all',
          elementType: 'geometry',
          stylers: [{ saturation: -20 }]
        }
      ],
      disableDefaultUI: false,
      zoomControl: true,
      mapTypeControl: false,
      scaleControl: true,
      streetViewControl: false,
      rotateControl: false,
      fullscreenControl: true
    });

    marker = new google.maps.Marker({
      position: defaultLocation,
      map: map,
      draggable: true,
      animation: google.maps.Animation.DROP,
      title: 'Your Delivery Location',
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        scale: 12,
        fillColor: '#667eea',
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 3
      }
    });

    // Update coordinates when marker is dragged
    marker.addListener('dragend', function() {
      const position = marker.getPosition();
      updateCoordinates(position.lat(), position.lng());
      geocodeLocation(position.lat(), position.lng());
      checkEligibility();
    });

    // Get current location on load if available
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition((position) => {
        const pos = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };
        map.setCenter(pos);
        marker.setPosition(pos);
        updateCoordinates(pos.lat, pos.lng);
        geocodeLocation(pos.lat, pos.lng);
      });
    }
  }

  function selectDeliveryType(type) {
    selectedDeliveryType = type;
    document.getElementById('delivery-type-input').value = type;
    
    // Update UI with smooth transitions
    const expressOption = document.getElementById('express-option');
    const standardOption = document.getElementById('standard-option');
    
    expressOption.classList.toggle('selected', type === 'express');
    standardOption.classList.toggle('selected', type === 'standard');
    
    // Add scale animation
    if (type === 'express') {
      expressOption.style.transform = 'scale(1.02)';
      standardOption.style.transform = 'scale(1)';
    } else {
      standardOption.style.transform = 'scale(1.02)';
      expressOption.style.transform = 'scale(1)';
    }
    
    setTimeout(() => {
      expressOption.style.transform = '';
      standardOption.style.transform = '';
    }, 300);
  }

  function selectPayment(method) {
    document.getElementById('razorpay').checked = (method === 'razorpay');
    document.getElementById('cod').checked = (method === 'cod');
    
    // Update visual state
    document.querySelectorAll('.payment-option').forEach(option => {
      option.classList.remove('selected');
    });
    
    if (method === 'razorpay') {
      document.querySelector('.payment-option:first-child').classList.add('selected');
    } else {
      document.querySelector('.payment-option:last-child').classList.add('selected');
    }
  }

  function detectLocation() {
    const button = event.target.closest('.location-button');
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Detecting...';
    button.disabled = true;
    
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition((position) => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        
        const pos = { lat, lng };
        map.setCenter(pos);
        marker.setPosition(pos);
        marker.setAnimation(google.maps.Animation.BOUNCE);
        setTimeout(() => marker.setAnimation(null), 2000);
        
        updateCoordinates(lat, lng);
        geocodeLocation(lat, lng);
        checkEligibility();
        
        button.innerHTML = '<i class="bi bi-check-circle-fill"></i> Location Detected!';
        setTimeout(() => {
          button.innerHTML = '<i class="bi bi-crosshair"></i> Use My Current Location';
          button.disabled = false;
        }, 2000);
      }, () => {
        alert('Unable to retrieve your location. Please enter address manually.');
        button.innerHTML = '<i class="bi bi-crosshair"></i> Use My Current Location';
        button.disabled = false;
      });
    } else {
      alert('Geolocation is not supported by your browser.');
      button.innerHTML = '<i class="bi bi-crosshair"></i> Use My Current Location';
      button.disabled = false;
    }
  }

  function updateCoordinates(lat, lng) {
    document.getElementById('latitude-input').value = lat;
    document.getElementById('longitude-input').value = lng;
  }

  function geocodeLocation(lat, lng) {
    const geocoder = new google.maps.Geocoder();
    geocoder.geocode({ location: { lat, lng } }, (results, status) => {
      if (status === 'OK' && results[0]) {
        const addressComponents = results[0].address_components;
        
        // Auto-fill address fields with smooth animation
        addressComponents.forEach(component => {
          if (component.types.includes('locality')) {
            const cityInput = document.getElementById('city-input');
            cityInput.value = component.long_name;
            cityInput.style.borderColor = '#38ef7d';
            setTimeout(() => cityInput.style.borderColor = '', 1000);
          }
          if (component.types.includes('administrative_area_level_1')) {
            const stateInput = document.getElementById('state-input');
            stateInput.value = component.long_name;
            stateInput.style.borderColor = '#38ef7d';
            setTimeout(() => stateInput.style.borderColor = '', 1000);
          }
          if (component.types.includes('postal_code')) {
            const pincodeInput = document.getElementById('pincode-input');
            pincodeInput.value = component.long_name;
            pincodeInput.style.borderColor = '#38ef7d';
            setTimeout(() => pincodeInput.style.borderColor = '', 1000);
          }
        });

        const addressInput = document.getElementById('address-input');
        addressInput.value = results[0].formatted_address;
        addressInput.style.borderColor = '#38ef7d';
        setTimeout(() => addressInput.style.borderColor = '', 1000);
      }
    });
  }

  function checkEligibility() {
    const lat = document.getElementById('latitude-input').value;
    const lng = document.getElementById('longitude-input').value;
    const city = document.getElementById('city-input').value;
    const state = document.getElementById('state-input').value;
    const pincode = document.getElementById('pincode-input').value;
    const address = document.getElementById('address-input').value;

    if (!lat || !lng || !city || !state || !pincode) {
      return;
    }

    fetch('{{ route("orders.checkQuickDelivery") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        address, city, state, pincode,
        store_id: 1 // Default store
      })
    })
    .then(res => res.json())
    .then(data => {
      const statusDiv = document.getElementById('eligibility-status');
      
      if (data.eligible) {
        statusDiv.innerHTML = `
          <div class="eligibility-badge eligible" style="animation: slideIn 0.5s ease-out;">
            <i class="bi bi-check-circle-fill" style="font-size: 1.5rem;"></i>
            <div>
              <div style="font-weight: 900;">⚡ 10-Minute Delivery Available!</div>
              <div style="font-size: 0.9rem; opacity: 0.9;">You're ${data.distance_km} km away from the store</div>
            </div>
          </div>
        `;
      } else {
        statusDiv.innerHTML = `
          <div class="eligibility-badge not-eligible" style="animation: slideIn 0.5s ease-out;">
            <i class="bi bi-info-circle-fill" style="font-size: 1.5rem;"></i>
            <div>
              <div style="font-weight: 900;">📦 Standard Delivery Available</div>
              <div style="font-size: 0.9rem; opacity: 0.9;">You're ${data.distance_km} km away from the store</div>
            </div>
          </div>
        `;
      }
    })
    .catch(error => console.error('Error checking eligibility:', error));
  }

  // Initialize map when Google Maps loads
  window.initMap = initMap;

  // Check eligibility when address changes
  document.getElementById('address-input').addEventListener('blur', checkEligibility);
  document.getElementById('city-input').addEventListener('blur', checkEligibility);
  document.getElementById('pincode-input').addEventListener('blur', checkEligibility);

  // Form submission with loading animation
  document.getElementById('checkout-form').addEventListener('submit', function(e) {
    e.preventDefault();
    document.getElementById('loading-overlay').classList.add('active');
    
    // Submit after animation
    setTimeout(() => {
      this.submit();
    }, 1000);
  });

  // Initialize selections
  selectDeliveryType('express');
  selectPayment('razorpay');

  // Add animation CSS
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  `;
  document.head.appendChild(style);
</script>
@endsection
