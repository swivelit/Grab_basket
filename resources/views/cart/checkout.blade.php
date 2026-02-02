<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout - GrabBaskets</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  
  <script
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=places"
    async defer></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: #f5f5f5;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    /* Location Bar Below Navbar */
    .location-bar {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 12px 0;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .location-content {
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
    }

    .location-icon {
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
    }

    .location-text {
      flex: 1;
    }

    .location-label {
      font-size: 0.75rem;
      opacity: 0.9;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .location-address {
      font-size: 1rem;
      font-weight: 600;
      line-height: 1.3;
    }

    .change-location-btn {
      background: rgba(255, 255, 255, 0.2);
      border: 2px solid rgba(255, 255, 255, 0.4);
      color: white;
      padding: 8px 20px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .change-location-btn:hover {
      background: rgba(255, 255, 255, 0.3);
      border-color: rgba(255, 255, 255, 0.6);
    }

    /* Checkout Progress Tabs */
    .checkout-tabs {
      background: white;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      margin-bottom: 30px;
      border-radius: 12px;
      overflow: hidden;
    }

    .tab-nav {
      display: flex;
      border-bottom: 3px solid #e0e0e0;
    }

    .tab-item {
      flex: 1;
      padding: 20px 30px;
      text-align: center;
      cursor: pointer;
      position: relative;
      transition: all 0.3s;
      background: #fafafa;
      border-bottom: 3px solid transparent;
      margin-bottom: -3px;
    }

    .tab-item:hover {
      background: #f5f5f5;
    }

    .tab-item.active {
      background: white;
      border-bottom: 3px solid #667eea;
      font-weight: 700;
    }

    .tab-item.completed {
      background: #e8f5e9;
    }

    .tab-number {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #e0e0e0;
      color: #666;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      margin-bottom: 8px;
      transition: all 0.3s;
    }

    .tab-item.active .tab-number {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      transform: scale(1.1);
    }

    .tab-item.completed .tab-number {
      background: #4caf50;
      color: white;
    }

    .tab-title {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .tab-subtitle {
      font-size: 0.8rem;
      color: #666;
    }

    /* Tab Content */
    .tab-content-wrapper {
      display: none;
    }

    .tab-content-wrapper.active {
      display: block;
      animation: fadeIn 0.4s ease-in;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Address Cards */
    .address-card {
      background: white;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      padding: 20px;
      cursor: pointer;
      transition: all 0.3s;
      margin-bottom: 16px;
      position: relative;
    }

    .address-card:hover {
      border-color: #667eea;
      box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
      transform: translateY(-2px);
    }

    .address-card.selected {
      border-color: #667eea;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
      box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
    }

    .address-type-badge {
      position: absolute;
      top: 16px;
      right: 16px;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
    }

    .badge-home {
      background: #e3f2fd;
      color: #1976d2;
    }

    .badge-office {
      background: #fff3e0;
      color: #f57c00;
    }

    .badge-other {
      background: #f3e5f5;
      color: #7b1fa2;
    }

    .address-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      margin-bottom: 12px;
    }

    .address-text {
      font-size: 1rem;
      color: #333;
      line-height: 1.6;
      margin-bottom: 8px;
    }

    .address-details {
      font-size: 0.85rem;
      color: #666;
    }

    /* Map Container */
    #map {
      height: 400px;
      width: 100%;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Order Summary Card */
    .order-summary {
      background: white;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
      position: sticky;
      top: 100px;
    }

    .summary-header {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 20px;
      padding-bottom: 16px;
      border-bottom: 2px solid #f0f0f0;
    }

    .summary-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 0;
      border-bottom: 1px solid #f5f5f5;
    }

    .item-image {
      width: 60px;
      height: 60px;
      border-radius: 8px;
      object-fit: cover;
    }

    .item-info {
      flex: 1;
    }

    .item-name {
      font-weight: 600;
      font-size: 0.95rem;
      margin-bottom: 4px;
    }

    .item-qty {
      font-size: 0.85rem;
      color: #666;
    }

    .item-price {
      font-weight: 700;
      color: #333;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      font-size: 0.95rem;
    }

    .summary-total {
      display: flex;
      justify-content: space-between;
      padding: 16px 0;
      border-top: 2px solid #e0e0e0;
      margin-top: 12px;
      font-size: 1.3rem;
      font-weight: 700;
    }

    /* Payment Options */
    .payment-option {
      background: white;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      padding: 20px;
      cursor: pointer;
      transition: all 0.3s;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .payment-option:hover {
      border-color: #667eea;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }

    .payment-option.selected {
      border-color: #667eea;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    }

    .payment-radio {
      width: 24px;
      height: 24px;
      accent-color: #667eea;
    }

    .payment-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      background: linear-gradient(135deg, #4caf50 0%, #8bc34a 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
    }

    .payment-info {
      flex: 1;
    }

    .payment-title {
      font-weight: 700;
      font-size: 1rem;
      margin-bottom: 4px;
    }

    .payment-desc {
      font-size: 0.85rem;
      color: #666;
    }

    /* Buttons */
    .btn-primary-custom {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 16px 32px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 1.1rem;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
    }

    .btn-primary-custom:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary-custom {
      background: white;
      color: #667eea;
      border: 2px solid #667eea;
      padding: 14px 32px;
      border-radius: 12px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-secondary-custom:hover {
      background: #667eea;
      color: white;
    }

    .btn-add-address {
      background: white;
      border: 2px dashed #667eea;
      color: #667eea;
      padding: 20px;
      border-radius: 12px;
      width: 100%;
      cursor: pointer;
      transition: all 0.3s;
      font-weight: 600;
    }

    .btn-add-address:hover {
      background: rgba(102, 126, 234, 0.05);
      border-style: solid;
    }

    /* Form Styling */
    .form-control-custom {
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 1rem;
      transition: all 0.3s;
    }

    .form-control-custom:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    /* Delivery Options */
    .delivery-options-container {
      background: white;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
      margin-bottom: 24px;
    }

    .delivery-option-card {
      background: white;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      padding: 20px;
      cursor: pointer;
      transition: all 0.3s;
      margin-bottom: 16px;
      position: relative;
      display: flex;
      align-items: flex-start;
      gap: 16px;
    }

    .delivery-option-card:hover {
      border-color: #667eea;
      box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
      transform: translateY(-2px);
    }

    .delivery-option-card.selected {
      border-color: #667eea;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
      box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
    }

    .delivery-option-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      flex-shrink: 0;
    }

    .fast-delivery-icon {
      background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
      color: white;
    }

    .standard-delivery-icon {
      background: linear-gradient(135deg, #4facfe, #00f2fe);
      color: white;
    }

    .delivery-option-content {
      flex: 1;
    }

    .delivery-option-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
    }

    .delivery-option-title {
      font-size: 1.2rem;
      font-weight: 700;
      color: #333;
    }

    .delivery-badge {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .badge-fast {
      background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
      color: white;
    }

    .badge-standard {
      background: linear-gradient(135deg, #4facfe, #00f2fe);
      color: white;
    }

    .delivery-option-time {
      font-size: 1rem;
      color: #666;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .delivery-option-description {
      font-size: 0.9rem;
      color: #888;
      line-height: 1.5;
    }

    .delivery-coverage {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #fff3cd;
      color: #856404;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-top: 8px;
    }

    .delivery-price {
      font-size: 1.1rem;
      font-weight: 700;
      color: #667eea;
      margin-top: 8px;
    }

    .delivery-radio {
      position: absolute;
      top: 20px;
      right: 20px;
      width: 24px;
      height: 24px;
      accent-color: #667eea;
      cursor: pointer;
    }

    .distance-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #e8f5e9;
      color: #2e7d32;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 8px;
    }

    /* ============ BLINKIT-STYLE ENHANCEMENTS ============ */

    /* Real-time Delivery Tracker */
    .delivery-tracker {
      background: linear-gradient(135deg, #fff9e6 0%, #fff4d1 100%);
      border: 2px dashed #ffa500;
      border-radius: 16px;
      padding: 16px;
      margin-top: 12px;
      position: relative;
      overflow: hidden;
    }

    .delivery-tracker::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
      animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
      0% {
        left: -100%;
      }

      100% {
        left: 100%;
      }
    }

    .tracker-steps {
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      margin: 20px 0;
    }

    .tracker-step {
      flex: 1;
      text-align: center;
      position: relative;
      z-index: 1;
    }

    .tracker-step-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: white;
      border: 3px solid #e0e0e0;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 8px;
      font-size: 1.2rem;
      transition: all 0.3s;
    }

    .tracker-step.active .tracker-step-icon {
      background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
      border-color: #ff6b6b;
      color: white;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {

      0%,
      100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7);
      }

      50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 10px rgba(255, 107, 107, 0);
      }
    }

    .tracker-step.completed .tracker-step-icon {
      background: #4caf50;
      border-color: #4caf50;
      color: white;
    }

    .tracker-step-label {
      font-size: 0.75rem;
      color: #666;
      font-weight: 600;
    }

    .tracker-step.active .tracker-step-label {
      color: #ff6b6b;
      font-weight: 700;
    }

    .tracker-line {
      position: absolute;
      top: 20px;
      left: 0;
      right: 0;
      height: 3px;
      background: #e0e0e0;
      z-index: 0;
    }

    .tracker-line-progress {
      height: 100%;
      background: linear-gradient(90deg, #ff6b6b, #ee5a6f);
      width: 0%;
      transition: width 0.5s ease;
    }

    /* Guaranteed Time Badge */
    .guaranteed-time-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: linear-gradient(135deg, #4caf50, #45a049);
      color: white;
      padding: 8px 16px;
      border-radius: 25px;
      font-size: 0.9rem;
      font-weight: 700;
      box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
      animation: bounce 2s infinite;
    }

    @keyframes bounce {

      0%,
      100% {
        transform: translateY(0);
      }

      50% {
        transform: translateY(-3px);
      }
    }

    .guaranteed-time-badge i {
      font-size: 1.1rem;
      animation: spin 2s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    /* Time Slot Picker */
    .time-slot-picker {
      margin-top: 16px;
      padding: 16px;
      background: #f8f9fa;
      border-radius: 12px;
    }

    .time-slot-label {
      font-size: 0.95rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .time-slots {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 12px;
    }

    .time-slot {
      padding: 12px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
      background: white;
    }

    .time-slot:hover {
      border-color: #ff6b6b;
      transform: scale(1.02);
    }

    .time-slot.selected {
      border-color: #ff6b6b;
      background: linear-gradient(135deg, rgba(255, 107, 107, 0.1), rgba(238, 90, 111, 0.1));
    }

    .time-slot-time {
      font-weight: 700;
      color: #333;
      font-size: 0.9rem;
    }

    .time-slot-label-text {
      font-size: 0.75rem;
      color: #666;
      margin-top: 4px;
    }

    /* Live Delivery Person Info */
    .delivery-person-info {
      background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
      border-radius: 12px;
      padding: 16px;
      margin-top: 16px;
      display: flex;
      align-items: center;
      gap: 16px;
      border: 2px solid #2196f3;
    }

    .delivery-person-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #2196f3, #1976d2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      color: white;
      flex-shrink: 0;
      border: 3px solid white;
      box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
    }

    .delivery-person-details {
      flex: 1;
    }

    .delivery-person-name {
      font-weight: 700;
      color: #1976d2;
      font-size: 1rem;
      margin-bottom: 4px;
    }

    .delivery-person-status {
      font-size: 0.85rem;
      color: #666;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #4caf50;
      animation: blink 1.5s infinite;
    }

    @keyframes blink {

      0%,
      100% {
        opacity: 1;
      }

      50% {
        opacity: 0.3;
      }
    }

    .delivery-person-eta {
      background: white;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
      color: #ff6b6b;
      margin-top: 6px;
      display: inline-block;
    }

    /* Product Freshness Indicators */
    .freshness-indicator {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 12px;
      padding: 12px;
      background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
      border-radius: 10px;
      border: 2px solid #4caf50;
    }

    .freshness-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #4caf50;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      flex-shrink: 0;
    }

    .freshness-text {
      font-size: 0.9rem;
      color: #2e7d32;
      font-weight: 600;
      line-height: 1.4;
    }

    /* Smart Pricing Display */
    .smart-pricing {
      background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
      border-radius: 12px;
      padding: 16px;
      margin-top: 16px;
      border: 2px dashed #ff9800;
    }

    .pricing-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }

    .pricing-label {
      font-size: 0.9rem;
      color: #666;
    }

    .pricing-value {
      font-size: 0.95rem;
      font-weight: 700;
      color: #333;
    }

    .pricing-value.discount {
      color: #4caf50;
    }

    .pricing-total {
      border-top: 2px solid #ff9800;
      padding-top: 12px;
      margin-top: 8px;
    }

    .pricing-total .pricing-value {
      font-size: 1.2rem;
      color: #ff6b6b;
    }

    /* Delivery Partner Badge */
    .partner-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: linear-gradient(135deg, #9c27b0, #7b1fa2);
      color: white;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
      margin-top: 8px;
    }

    .partner-badge i {
      font-size: 0.9rem;
    }

    /* Live GPS Tracking Indicator */
    .gps-tracking {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 16px;
      background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
      border-radius: 25px;
      border: 2px solid #9c27b0;
      margin-top: 12px;
    }

    .gps-icon {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #9c27b0;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.9rem;
      animation: pulseGPS 1.5s infinite;
    }

    @keyframes pulseGPS {

      0%,
      100% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.1);
      }
    }

    .gps-text {
      font-size: 0.85rem;
      font-weight: 600;
      color: #7b1fa2;
    }

    /* Mobile-specific hiding */
    @media (max-width: 768px) {

      /* Hide all floating elements on mobile */
      #floatingActionsContainer,
      #showFabBtn,
      .floating-actions,
      .fab-main,
      .chatbot-widget,
      [id*="chatbot"],
      [class*="chatbot"],
      .floating-menu-popup {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
      }

      /* Adjust delivery cards for mobile */
      .delivery-option-card {
        flex-direction: column;
        padding: 16px;
      }

      .delivery-option-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
      }

      .tracker-steps {
        flex-wrap: nowrap;
        overflow-x: auto;
      }

      .time-slots {
        grid-template-columns: repeat(2, 1fr);
      }

      .delivery-person-info {
        flex-direction: column;
        text-align: center;
      }
    }

    /* Responsive */
    @media (max-width: 768px) {
      .tab-item {
        padding: 16px 12px;
      }

      .tab-title {
        font-size: 0.9rem;
      }

      .tab-subtitle {
        display: none;
      }

      .order-summary {
        position: relative;
        top: 0;
        margin-top: 20px;
      }

      #map {
        height: 300px;
      }
    }

    /* Loading Animation */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(102, 126, 234, 0.95);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }

    .loading-overlay.active {
      display: flex;
    }

    .spinner {
      width: 60px;
      height: 60px;
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }
  </style>
</head>

<body>
  <x-back-button />

  <nav class="navbar navbar-expand-lg navbar-dark" style="background-color:rgb(30, 30, 55);">
    <div class="container-fluid">

      <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center">
        <img src="{{ asset('asset/images/logo-image.png') }}" alt="Logo" width="150" class="me-2">
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
        aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarContent">

        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item d-none d-lg-block me-2">
            <span class="text-light small">Hello, {{ Auth::user()->name }}</span>
          </li>

          <li class="nav-item dropdown">
            <a class="btn btn-outline-warning btn-sm dropdown-toggle d-flex align-items-center gap-1" href="/cart"
              aria-expanded="false">
              <i class="bi bi-person-circle"></i> Cart
            </a>

          </li>

          <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
            <a href="{{ route('logout') }}" class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1 w-100"
              onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
              @csrf
            </form>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Location Bar -->
  <div class="location-bar">
    <div class="container">
      <div class="location-content" onclick="detectLocationAuto()">
        <div class="location-icon">
          <i class="bi bi-geo-alt-fill"></i>
        </div>
        <div class="location-text">
          <div class="location-label">Delivering to</div>
          <div class="location-address" id="current-location">
            <i class="bi bi-hourglass-split"></i> Detecting your location...
          </div>
        </div>
        <button class="change-location-btn" type="button">
          <i class="bi bi-arrow-repeat"></i> Change
        </button>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <!-- Checkout Progress Tabs -->
    <div class="checkout-tabs">
      <div class="tab-nav">
        <div class="tab-item active" data-tab="address">
          <div class="tab-number">1</div>
          <div class="tab-title">Delivery Address</div>
          <div class="tab-subtitle">Where should we deliver?</div>
        </div>
        <div class="tab-item" data-tab="delivery">
          <div class="tab-number">2</div>
          <div class="tab-title">Delivery Type</div>
          <div class="tab-subtitle">Fast or Standard</div>
        </div>
        <div class="tab-item" data-tab="payment">
          <div class="tab-number">3</div>
          <div class="tab-title">Payment Method</div>
          <div class="tab-subtitle">Complete your order</div>
        </div>
      </div>
    </div>

    <form id="checkout-form" method="POST" action="{{ route('cart.checkout') }}">
      @csrf
      <input type="hidden" name="delivery_type" id="delivery_type" value="standard">
      <input type="hidden" name="user_latitude" id="user_latitude">
      <input type="hidden" name="user_longitude" id="user_longitude">

      <div class="row g-4">
        <!-- Left Column - Tab Content -->
        <div class="col-lg-8">

          <!-- Step 1: Address Tab -->
          <div class="tab-content-wrapper active" id="address-tab">
            <h4 class="mb-4 fw-bold">
              <i class="bi bi-geo-alt-fill text-primary"></i> Select Delivery Address
            </h4>

            <!-- Existing Addresses -->
            @if(count($addresses) > 0)
            <div class="mb-4">
              <h6 class="text-muted mb-3">SAVED ADDRESSES</h6>
              @foreach($addresses as $index => $addr)
              <div class="address-card" onclick="selectAddress({{ $index }}, '{{ $addr }}')">
                <div class="address-type-badge badge-home">
                  <i class="bi bi-house-fill"></i> Home
                </div>
                <div class="address-icon">
                  <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div class="address-text">{{ $addr }}</div>
                <div class="address-details">
                  <i class="bi bi-telephone-fill"></i> {{ auth()->user()->phone ?? 'No phone' }}
                </div>
                <input type="radio" name="address" value="{{ $addr }}" style="display: none;" id="addr-{{ $index }}">
              </div>
              @endforeach
            </div>
            @endif

            <!-- Add New Address Form -->
            <div class="mb-4">
              <button type="button" class="btn-add-address" onclick="toggleAddressForm()">
                <i class="bi bi-plus-circle" style="font-size: 1.5rem;"></i>
                <div class="mt-2">Add New Address</div>
              </button>

              <div id="new-address-form" style="display: none; margin-top: 20px;">
                <div class="card p-4">
                  <h6 class="fw-bold mb-3">
                    <i class="bi bi-map"></i> Enter New Address
                  </h6>

                  <!-- Google Map -->
                  <div id="map"></div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Full Address</label>
                    <textarea name="new_address" id="new_address" class="form-control-custom w-100" rows="3"
                      placeholder="House/Flat no, Street name, Area, Landmark"></textarea>
                  </div>

                  <div class="row g-3 mb-3">
                    <div class="col-md-4">
                      <label class="form-label fw-semibold">City</label>
                      <input type="text" name="city" id="city" class="form-control-custom w-100"
                        value="{{ old('city', $user->city) }}" placeholder="City">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label fw-semibold">State</label>
                      <input type="text" name="state" id="state" class="form-control-custom w-100"
                        value="{{ old('state', $user->state) }}" placeholder="State">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Pincode</label>
                      <input type="text" name="pincode" id="pincode" class="form-control-custom w-100"
                        value="{{ old('pincode', $user->pincode) }}" placeholder="6-digit pincode" pattern="[0-9]{6}">
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Address Type</label>
                    <div class="btn-group w-100" role="group">
                      <input type="radio" class="btn-check" name="address_type" id="type-home" value="home" checked>
                      <label class="btn btn-outline-primary" for="type-home">
                        <i class="bi bi-house-fill"></i> Home
                      </label>

                      <input type="radio" class="btn-check" name="address_type" id="type-office" value="office">
                      <label class="btn btn-outline-primary" for="type-office">
                        <i class="bi bi-building"></i> Office
                      </label>

                      <input type="radio" class="btn-check" name="address_type" id="type-other" value="other">
                      <label class="btn btn-outline-primary" for="type-other">
                        <i class="bi bi-geo"></i> Other
                      </label>
                    </div>
                  </div>

                  <button type="button" class="btn btn-primary w-100" onclick="saveNewAddress()">
                    <i class="bi bi-check-circle-fill"></i> Save & Continue
                  </button>
                </div>
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-end gap-3 mt-4">
              <button type="button" class="btn-primary-custom" onclick="goToDelivery()">
                Continue to Delivery Options <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>

          <!-- Step 2: Delivery Options Tab -->
          <div class="tab-content-wrapper" id="delivery-tab">
            <h4 class="mb-4 fw-bold">
              <i class="bi bi-lightning-fill text-warning"></i> Choose Delivery Speed
            </h4>

            <div class="delivery-options-container">
              <p class="text-muted mb-4">
                <i class="bi bi-info-circle"></i> All products support both delivery types. Choose based on your
                urgency.
              </p>

              <!-- Fast Delivery Option - Blinkit Style Enhanced -->
              <div class="delivery-option-card" onclick="selectDeliveryType('fast')" id="fast-delivery-option">
                <input type="radio" name="delivery_option" value="fast" class="delivery-radio" id="fast-delivery">
                <div class="delivery-option-icon fast-delivery-icon">
                  <i class="bi bi-lightning-charge-fill"></i>
                </div>
                <div class="delivery-option-content">
                  <div class="delivery-option-header">
                    <div class="delivery-option-title">Express Delivery</div>
                    <span class="delivery-badge badge-fast">⚡ FASTEST</span>
                    <span class="distance-badge" id="fast-distance-badge" style="display: none;">
                      <i class="bi bi-check-circle-fill"></i> Available
                    </span>
                  </div>
                  <div class="delivery-option-time">
                    <i class="bi bi-clock-fill"></i>
                    <strong>Delivery in 10 minutes</strong>
                  </div>

                  <!-- Guaranteed Time Badge -->
                  <div class="guaranteed-time-badge">
                    <i class="bi bi-shield-check-fill"></i>
                    <span>100% On-Time Guarantee or FREE</span>
                  </div>

                  <div class="delivery-option-description">
                    Get your order delivered at lightning speed! Perfect for urgent needs.
                  </div>

                  <!-- Real-time Delivery Tracker -->
                  <div class="delivery-tracker" id="express-tracker" style="display: none;">
                    <div
                      style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                      <strong style="color: #ff6b6b; font-size: 0.95rem;">
                        <i class="bi bi-truck"></i> Track Your Order
                      </strong>
                      <span style="font-size: 0.85rem; color: #666;">ETA: <strong id="delivery-eta">10
                          mins</strong></span>
                    </div>
                    <div class="tracker-steps">
                      <div class="tracker-line">
                        <div class="tracker-line-progress" id="tracker-progress"></div>
                      </div>
                      <div class="tracker-step active" id="step-1">
                        <div class="tracker-step-icon">
                          <i class="bi bi-check2"></i>
                        </div>
                        <div class="tracker-step-label">Order Placed</div>
                      </div>
                      <div class="tracker-step" id="step-2">
                        <div class="tracker-step-icon">
                          <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="tracker-step-label">Packing</div>
                      </div>
                      <div class="tracker-step" id="step-3">
                        <div class="tracker-step-icon">
                          <i class="bi bi-bicycle"></i>
                        </div>
                        <div class="tracker-step-label">On the Way</div>
                      </div>
                      <div class="tracker-step" id="step-4">
                        <div class="tracker-step-icon">
                          <i class="bi bi-house-door"></i>
                        </div>
                        <div class="tracker-step-label">Delivered</div>
                      </div>
                    </div>
                  </div>

                  <!-- Time Slot Picker -->
                  <div class="time-slot-picker" id="express-time-slots" style="display: none;">
                    <div class="time-slot-label">
                      <i class="bi bi-clock-history"></i>
                      <span>Choose your delivery slot:</span>
                    </div>
                    <div class="time-slots">
                      <div class="time-slot selected" onclick="selectTimeSlot(this, 'now')">
                        <div class="time-slot-time">Now</div>
                        <div class="time-slot-label-text">Next 10 mins</div>
                      </div>
                      <div class="time-slot" onclick="selectTimeSlot(this, '10-20')">
                        <div class="time-slot-time">10-20 mins</div>
                        <div class="time-slot-label-text">Flexible timing</div>
                      </div>
                      <div class="time-slot" onclick="selectTimeSlot(this, '20-30')">
                        <div class="time-slot-time">20-30 mins</div>
                        <div class="time-slot-label-text">Later today</div>
                      </div>
                    </div>
                  </div>

                  <!-- Live Delivery Person Info -->
                  <div class="delivery-person-info" id="express-delivery-person" style="display: none;">
                    <div class="delivery-person-avatar">
                      <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="delivery-person-details">
                      <div class="delivery-person-name" id="delivery-person-name">Delivery Partner</div>
                      <div class="delivery-person-status">
                        <span class="status-dot"></span>
                        <span id="delivery-person-status">Will be assigned soon</span>
                      </div>
                      <div class="delivery-person-eta">
                        <i class="bi bi-clock-fill"></i> Arriving in <span id="live-eta">10</span> mins
                      </div>
                    </div>
                  </div>

                  <!-- Product Freshness Indicator -->
                  <div class="freshness-indicator">
                    <div class="freshness-icon">
                      <i class="bi bi-snow"></i>
                    </div>
                    <div class="freshness-text">
                      <strong>Fresh Guarantee:</strong> Products packed & delivered with temperature control
                    </div>
                  </div>

                  <!-- GPS Live Tracking -->
                  <div class="gps-tracking" id="express-gps" style="display: none;">
                    <div class="gps-icon">
                      <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div class="gps-text">
                      Live GPS tracking enabled - Follow your order in real-time
                    </div>
                  </div>

                  <!-- Smart Pricing -->
                  <div class="smart-pricing">
                    <div class="pricing-row">
                      <span class="pricing-label">Express Delivery Fee</span>
                      <span class="pricing-value">₹49</span>
                    </div>
                    <div class="pricing-row" id="express-surge-pricing" style="display: none;">
                      <span class="pricing-label">
                        <i class="bi bi-info-circle-fill"></i> Peak Hour Surcharge
                      </span>
                      <span class="pricing-value">₹20</span>
                    </div>
                    <div class="pricing-row discount">
                      <span class="pricing-label">
                        <i class="bi bi-tag-fill"></i> First Order Discount
                      </span>
                      <span class="pricing-value discount">-₹25</span>
                    </div>
                    <div class="pricing-row pricing-total">
                      <span class="pricing-label"><strong>Total Delivery Cost</strong></span>
                      <span class="pricing-value">₹24</span>
                    </div>
                  </div>

                  <!-- Partner Badge -->
                  <div class="partner-badge">
                    <i class="bi bi-patch-check-fill"></i>
                    <span>Verified Delivery Partner</span>
                  </div>

                  <div class="delivery-coverage" id="fast-coverage-warning">
                    <i class="bi bi-geo-alt-fill"></i>
                    Available within 5km radius
                  </div>
                </div>
              </div>

              <!-- Standard Delivery Option -->
              <div class="delivery-option-card selected" onclick="selectDeliveryType('standard')"
                id="standard-delivery-option">
                <input type="radio" name="delivery_option" value="standard" class="delivery-radio"
                  id="standard-delivery" checked>
                <div class="delivery-option-icon standard-delivery-icon">
                  <i class="bi bi-truck"></i>
                </div>
                <div class="delivery-option-content">
                  <div class="delivery-option-header">
                    <div class="delivery-option-title">Standard Delivery</div>
                    <span class="delivery-badge badge-standard">📦 RELIABLE</span>
                  </div>
                  <div class="delivery-option-time">
                    <i class="bi bi-calendar-check-fill"></i>
                    <strong>Delivery in 1-2 days</strong>
                  </div>
                  <div class="delivery-option-description">
                    Regular delivery with guaranteed freshness and quality.
                  </div>
                  <div class="delivery-coverage" style="background: #e3f2fd; color: #0d47a1;">
                    <i class="bi bi-globe"></i>
                    Available everywhere
                  </div>
                  <div class="delivery-price">
                    Delivery Fee: FREE on orders above ₹299
                  </div>
                </div>
              </div>

              <!-- Distance Info -->
              <div class="alert alert-info mt-3" id="distance-info" style="display: none;">
                <i class="bi bi-info-circle-fill"></i>
                <strong>Your location:</strong> <span id="user-distance-text"></span> from store
              </div>

              <div class="alert alert-warning mt-3" id="fast-unavailable-warning" style="display: none;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Express Delivery Unavailable:</strong> You're outside the 5km coverage area. Standard delivery
                is available.
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex gap-3 justify-content-between mt-4">
              <button type="button" class="btn btn-outline-secondary btn-lg" onclick="goToAddress()">
                <i class="bi bi-arrow-left"></i> Back to Address
              </button>
              <button type="button" class="btn-primary-custom" onclick="goToPayment()">
                Continue to Payment <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>

          <!-- Step 3: Payment Tab -->
          <div class="tab-content-wrapper" id="payment-tab">
            <h4 class="mb-4 fw-bold">
              <i class="bi bi-credit-card-fill text-success"></i> Select Payment Method
            </h4>

            <!-- Payment Options -->
            <div class="payment-option selected" onclick="selectPayment('razorpay')">
              <input type="radio" name="payment_method" id="razorpay" value="razorpay" class="payment-radio" checked>
              <div class="payment-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="bi bi-credit-card-fill"></i>
              </div>
              <div class="payment-info">
                <div class="payment-title">Razorpay Payment Gateway</div>
                <div class="payment-desc">Pay securely using Cards, UPI, Wallets & Net Banking</div>
              </div>
            </div>

            <div class="payment-option" onclick="selectPayment('cod')">
              <input type="radio" name="payment_method" id="cod" value="cod" class="payment-radio">
              <div class="payment-icon" style="background: linear-gradient(135deg, #4caf50 0%, #8bc34a 100%);">
                <i class="bi bi-cash-coin"></i>
              </div>
              <div class="payment-info">
                <div class="payment-title">Cash on Delivery (COD)</div>
                <div class="payment-desc">Pay with cash when your order is delivered</div>
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-between gap-3 mt-4">
              <button type="button" class="btn-secondary-custom" onclick="goToAddress()">
                <i class="bi bi-arrow-left"></i> Back to Address
              </button>
              <button type="button" class="btn-primary-custom" id="place-order-btn">
                <i class="bi bi-shield-check-fill"></i> <span id="btn-text">Place Secure Order</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Right Column - Order Summary -->
        <div class="col-lg-4">
          <div class="order-summary">
            <div class="summary-header">
              <i class="bi bi-bag-check-fill"></i> Order Summary
            </div>

            <!-- Cart Items -->
            <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
              @foreach($items as $item)
              <div class="summary-item">
                @php
                $imageUrl = null;
                // Check multiple image sources
                if (!empty($item->product->image)) {
                $imageUrl = $item->product->image;
                } elseif (!empty($item->product->images) && is_array(json_decode($item->product->images, true))) {
                $images = json_decode($item->product->images, true);
                $imageUrl = !empty($images) ? $images[0] : null;
                } elseif (!empty($item->product->main_image)) {
                $imageUrl = $item->product->main_image;
                }

                // Ensure proper URL format
                if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $imageUrl = asset('storage/' . $imageUrl);
                }
                @endphp

                @if($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $item->product->name }}" class="item-image"
                  onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="item-image"
                  style="background: linear-gradient(135deg, #667eea, #764ba2); display: none; align-items: center; justify-content: center;">
                  <i class="bi bi-bag-fill" style="font-size: 1.5rem; color: white;"></i>
                </div>
                @else
                <div class="item-image"
                  style="background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center;">
                  <i class="bi bi-bag-fill" style="font-size: 1.5rem; color: white;"></i>
                </div>
                @endif
                <div class="item-info">
                  <div class="item-name">{{ $item->product->name }}</div>
                  <div class="item-qty">Qty: {{ $item->quantity }}</div>
                </div>
                <div class="item-price">₹{{ number_format($item->price * $item->quantity, 2) }}</div>
              </div>
              @endforeach
            </div>

            <!-- Price Breakdown -->
            <div class="summary-row">
              <span>Subtotal</span>
              <strong>₹{{ number_format($totals['subtotal'], 2) }}</strong>
            </div>

            <div class="summary-row">
              <span><i class="bi bi-tag-fill text-danger"></i> Discount</span>
              <strong class="text-danger">-₹{{ number_format($totals['discountTotal'], 2) }}</strong>
            </div>

            <div class="summary-row">
              <span><i class="bi bi-truck"></i> Delivery Charges</span>
              <strong class="text-success">
                @if($totals['deliveryTotal'] == 0)
                FREE
                @else
                ₹{{ number_format($totals['deliveryTotal'], 2) }}
                @endif
              </strong>
            </div>



            <div class="summary-total">
              <span>Total Amount</span>
              <strong style="color: #667eea;" id="final-total">₹{{ number_format($totals['total'], 2) }}</strong>
            </div>

            <div class="text-center mt-3 p-3" style="background: #f0f7ff; border-radius: 8px;">
              <i class="bi bi-shield-check text-primary"></i>
              <small class="text-muted d-block mt-1">Secure SSL Encrypted Payment</small>
            </div>
          </div>
        </div>
      </div>

      <input type="hidden" name="latitude" id="latitude">
      <input type="hidden" name="longitude" id="longitude">
    </form>
  </div>

  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loading-overlay">
    <div class="text-center text-white">
      <div class="spinner"></div>
      <div class="mt-3">Processing your order...</div>
    </div>
  </div>

  <script>
    let map;
    let marker;
    let selectedAddressIndex = null;
    let googleMapsLoaded = false;

    // Wait for Google Maps to load
    function initGoogleMaps() {
      if (typeof google !== 'undefined' && google.maps) {
        googleMapsLoaded = true;
        console.log('Google Maps loaded successfully');
      } else {
        console.log('Waiting for Google Maps to load...');
        setTimeout(initGoogleMaps, 200);
      }
    }

    // Auto-detect location on page load
    window.addEventListener('load', function() {
      console.log('Page loaded, starting location detection');
      detectLocationAuto();
      initGoogleMaps();
    });

    // Initialize Google Map
    function initMap() {
      // Check if Google Maps is loaded
      if (typeof google === 'undefined' || !google.maps) {
        console.error('Google Maps not loaded yet');
        setTimeout(initMap, 500);
        return;
      }

      const mapElement = document.getElementById('map');
      if (!mapElement) {
        console.error('Map element not found');
        return;
      }

      try {
        const defaultLocation = {
          lat: 12.9716,
          lng: 77.5946
        }; // Bangalore

        map = new google.maps.Map(mapElement, {
          zoom: 14,
          center: defaultLocation,
          styles: [{
            featureType: 'poi',
            elementType: 'labels',
            stylers: [{
              visibility: 'off'
            }]
          }],
          mapTypeControl: false,
          streetViewControl: false,
          fullscreenControl: true
        });

        marker = new google.maps.Marker({
          position: defaultLocation,
          map: map,
          draggable: true,
          animation: google.maps.Animation.DROP,
          title: 'Your Delivery Location'
        });

        marker.addListener('dragend', function() {
          const position = marker.getPosition();
          document.getElementById('latitude').value = position.lat();
          document.getElementById('longitude').value = position.lng();
          geocodeLocation(position.lat(), position.lng());
        });

        console.log('Map initialized successfully');

        // Try to get current location for map
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(
            (position) => {
              const pos = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
              };
              map.setCenter(pos);
              marker.setPosition(pos);
              marker.setAnimation(google.maps.Animation.BOUNCE);
              setTimeout(() => marker.setAnimation(null), 1500);

              document.getElementById('latitude').value = pos.lat;
              document.getElementById('longitude').value = pos.lng;
              geocodeLocation(pos.lat, pos.lng);
              console.log('Map centered to current location:', pos);
            },
            (error) => {
              console.error('Geolocation error for map:', error);
            }
          );
        }
      } catch (error) {
        console.error('Error initializing map:', error);
      }
    }

    // Auto-detect location for location bar
    function detectLocationAuto() {
      const locationElement = document.getElementById('current-location');

      if (!navigator.geolocation) {
        locationElement.innerHTML = '<i class="bi bi-geo-alt"></i> Geolocation not supported';
        return;
      }

      locationElement.innerHTML = '<i class="bi bi-hourglass-split"></i> Detecting your location...';
      console.log('Starting location detection');

      navigator.geolocation.getCurrentPosition(
        function(position) {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          console.log('Location detected:', lat, lng);

          // Use Google Geocoding API
          const apiKey = '{{ config("services.google.maps_api_key") }}';
          const geocodeUrl = `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${apiKey}`;

          fetch(geocodeUrl)
            .then(res => {
              if (!res.ok) {
                throw new Error('Geocoding API request failed');
              }
              return res.json();
            })
            .then(data => {
              console.log('Geocoding response:', data);

              if (data.status === 'OK' && data.results && data.results[0]) {
                const address = data.results[0].formatted_address;
                locationElement.innerHTML = `<i class="bi bi-geo-alt-fill"></i> ${address}`;

                // Update form fields
                const components = data.results[0].address_components;
                components.forEach(comp => {
                  if (comp.types.includes('locality')) {
                    document.getElementById('city').value = comp.long_name;
                  }
                  if (comp.types.includes('administrative_area_level_1')) {
                    document.getElementById('state').value = comp.long_name;
                  }
                  if (comp.types.includes('postal_code')) {
                    document.getElementById('pincode').value = comp.long_name;
                  }
                });

                console.log('Location detected successfully:', address);
              } else {
                console.error('Geocoding failed:', data.status);
                locationElement.innerHTML = '<i class="bi bi-geo-alt"></i> Unable to detect location';
              }
            })
            .catch((error) => {
              console.error('Geocoding error:', error);
              locationElement.innerHTML = '<i class="bi bi-geo-alt"></i> Unable to detect location';
            });
        },
        function(error) {
          console.error('Geolocation error:', error.message);
          locationElement.innerHTML = '<i class="bi bi-geo-alt"></i> Click to detect location';
        }, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0
        }
      );
    }

    // Geocode location
    function geocodeLocation(lat, lng) {
      console.log('Geocoding location:', lat, lng);
      const apiKey = '{{ config("services.google.maps_api_key") }}';
      const geocodeUrl = `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${apiKey}`;

      fetch(geocodeUrl)
        .then(res => {
          if (!res.ok) {
            throw new Error('Geocoding API request failed');
          }
          return res.json();
        })
        .then(data => {
          console.log('Geocoding response:', data);

          if (data.status === 'OK' && data.results && data.results[0]) {
            const addressComponents = data.results[0].address_components;

            // Fill address field
            const addressField = document.getElementById('new_address');
            if (addressField) {
              addressField.value = data.results[0].formatted_address;
            }

            // Fill city, state, pincode
            addressComponents.forEach(component => {
              if (component.types.includes('locality')) {
                const cityField = document.getElementById('city');
                if (cityField) cityField.value = component.long_name;
              }
              if (component.types.includes('administrative_area_level_1')) {
                const stateField = document.getElementById('state');
                if (stateField) stateField.value = component.long_name;
              }
              if (component.types.includes('postal_code')) {
                const pincodeField = document.getElementById('pincode');
                if (pincodeField) pincodeField.value = component.long_name;
              }
            });

            // Update location bar
            const locationElement = document.getElementById('current-location');
            if (locationElement) {
              locationElement.innerHTML = `<i class="bi bi-geo-alt-fill"></i> ${data.results[0].formatted_address}`;
            }

            console.log('Address fields updated successfully');
          } else {
            console.error('Geocoding failed:', data.status);
          }
        })
        .catch((error) => {
          console.error('Geocoding error:', error);
        });
    }

    // Tab Navigation
    document.querySelectorAll('.tab-item').forEach(tab => {
      tab.addEventListener('click', function() {
        const targetTab = this.dataset.tab;
        switchTab(targetTab);
      });
    });

    function switchTab(tabName) {
      // Update tab items
      document.querySelectorAll('.tab-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.tab === tabName) {
          item.classList.add('active');
        }
      });

      // Update tab content
      document.querySelectorAll('.tab-content-wrapper').forEach(content => {
        content.classList.remove('active');
      });
      document.getElementById(tabName + '-tab').classList.add('active');
    }

    function goToPayment() {
      // Validate delivery type selection
      const selectedDelivery = document.querySelector('input[name="delivery_option"]:checked');

      if (!selectedDelivery) {
        alert('Please select a delivery option');
        return;
      }

      // Mark delivery step as completed
      document.querySelector('.tab-item[data-tab="delivery"]').classList.add('completed');
      switchTab('payment');
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    }

    function goToAddress() {
      switchTab('address');
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    }

    function goToDelivery() {
      // Validate address selection
      const selectedAddress = document.querySelector('input[name="address"]:checked');
      const newAddress = document.getElementById('new_address').value;

      if (!selectedAddress && !newAddress) {
        alert('Please select or add a delivery address');
        return;
      }

      // Mark address step as completed
      document.querySelector('.tab-item[data-tab="address"]').classList.add('completed');

      // Calculate distance and check fast delivery availability
      checkDeliveryAvailability();

      switchTab('delivery');
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    }

    // Check delivery availability based on distance
    function checkDeliveryAvailability() {
      // Get user's latitude and longitude
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          function(position) {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;

            // Store coordinates in hidden fields
            document.getElementById('user_latitude').value = userLat;
            document.getElementById('user_longitude').value = userLng;

            // Store location (example: Theni, Tamil Nadu - you can change this)
            const storeLat = 10.0104; // Example: Theni latitude
            const storeLng = 77.4768; // Example: Theni longitude

            // Calculate distance
            const distance = calculateDistance(userLat, userLng, storeLat, storeLng);
            console.log('Distance from store:', distance, 'km');

            // Update UI based on distance
            document.getElementById('distance-info').style.display = 'block';
            document.getElementById('user-distance-text').textContent = distance.toFixed(2) + ' km';

            if (distance <= 5) {
              // Fast delivery available
              document.getElementById('fast-distance-badge').style.display = 'inline-flex';
              document.getElementById('fast-unavailable-warning').style.display = 'none';
              document.getElementById('fast-delivery-option').style.opacity = '1';
              document.getElementById('fast-delivery-option').style.pointerEvents = 'auto';
              document.getElementById('fast-delivery').disabled = false;
            } else {
              // Fast delivery unavailable
              document.getElementById('fast-distance-badge').style.display = 'none';
              document.getElementById('fast-unavailable-warning').style.display = 'block';
              document.getElementById('fast-delivery-option').style.opacity = '0.6';
              document.getElementById('fast-delivery-option').style.pointerEvents = 'none';
              document.getElementById('fast-delivery').disabled = true;

              // Auto-select standard delivery
              selectDeliveryType('standard');
            }
          },
          function(error) {
            console.error('Geolocation error:', error);
            // Default to standard delivery if location unavailable
            document.getElementById('fast-unavailable-warning').style.display = 'block';
            document.getElementById('fast-unavailable-warning').innerHTML =
              '<i class="bi bi-exclamation-triangle-fill"></i> <strong>Cannot detect location:</strong> Please enable location services. Standard delivery is available.';
            selectDeliveryType('standard');
          }
        );
      } else {
        console.error('Geolocation not supported');
        selectDeliveryType('standard');
      }
    }

    // Calculate distance between two coordinates (Haversine formula)
    function calculateDistance(lat1, lon1, lat2, lon2) {
      const R = 6371; // Radius of the Earth in kilometers
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLon = (lon2 - lon1) * Math.PI / 180;
      const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      const distance = R * c;
      return distance;
    }

    // Select delivery type
    function selectDeliveryType(type) {
      console.log('Selected delivery type:', type);

      // Update hidden field
      document.getElementById('delivery_type').value = type;

      // Remove selected class from all
      document.querySelectorAll('.delivery-option-card').forEach(card => {
        card.classList.remove('selected');
      });

      // Add selected class to clicked option
      if (type === 'fast') {
        document.getElementById('fast-delivery-option').classList.add('selected');
        document.getElementById('fast-delivery').checked = true;

        // Show Blinkit-style features for express delivery
        showExpressDeliveryFeatures();
      } else {
        document.getElementById('standard-delivery-option').classList.add('selected');
        document.getElementById('standard-delivery').checked = true;

        // Hide express features
        hideExpressDeliveryFeatures();
      }
    }

    // ============ BLINKIT-STYLE FEATURE FUNCTIONS ============

    // Show Express Delivery Features
    function showExpressDeliveryFeatures() {
      // Show time slot picker
      const timeSlotsElement = document.getElementById('express-time-slots');
      if (timeSlotsElement) {
        timeSlotsElement.style.display = 'block';
      }

      // Show delivery person info
      setTimeout(() => {
        const deliveryPersonElement = document.getElementById('express-delivery-person');
        if (deliveryPersonElement) {
          deliveryPersonElement.style.display = 'flex';
          simulateDeliveryPersonAssignment();
        }
      }, 1000);

      // Show GPS tracking
      setTimeout(() => {
        const gpsElement = document.getElementById('express-gps');
        if (gpsElement) {
          gpsElement.style.display = 'flex';
        }
      }, 1500);

      // Simulate delivery tracker after order
      // (Will be fully functional after actual order placement)
    }

    // Hide Express Delivery Features
    function hideExpressDeliveryFeatures() {
      const elementsToHide = [
        'express-time-slots',
        'express-delivery-person',
        'express-gps',
        'express-tracker'
      ];

      elementsToHide.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
          element.style.display = 'none';
        }
      });
    }

    // Time Slot Selection
    function selectTimeSlot(element, slot) {
      // Remove selected from all slots
      document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
      });

      // Add selected to clicked slot
      element.classList.add('selected');

      console.log('Selected time slot:', slot);

      // You can store the selected slot in a hidden field if needed
      // document.getElementById('selected_time_slot').value = slot;
    }

    // Simulate Delivery Person Assignment
    function simulateDeliveryPersonAssignment() {
      const names = ['Rajesh Kumar', 'Amit Singh', 'Priya Sharma', 'Vikram Patel', 'Sneha Reddy'];
      const randomName = names[Math.floor(Math.random() * names.length)];

      const nameElement = document.getElementById('delivery-person-name');
      const statusElement = document.getElementById('delivery-person-status');

      if (nameElement && statusElement) {
        // Simulate assignment animation
        statusElement.textContent = 'Finding nearby partner...';

        setTimeout(() => {
          nameElement.textContent = randomName;
          statusElement.innerHTML = '<span class="status-dot"></span> Partner assigned & heading to store';
        }, 2000);

        setTimeout(() => {
          statusElement.innerHTML = '<span class="status-dot"></span> Packing your order';
        }, 4000);

        setTimeout(() => {
          statusElement.innerHTML = '<span class="status-dot"></span> On the way to you!';
        }, 6000);
      }
    }

    // Start Live Delivery Tracking (Called after order is placed)
    function startLiveDeliveryTracking() {
      const tracker = document.getElementById('express-tracker');
      if (!tracker) return;

      tracker.style.display = 'block';

      let currentStep = 1;
      const totalSteps = 4;
      const stepDuration = 2500; // 2.5 seconds per step (10 mins / 4 steps)

      const interval = setInterval(() => {
        if (currentStep > totalSteps) {
          clearInterval(interval);
          return;
        }

        // Mark current step as completed
        const prevStep = document.getElementById(`step-${currentStep - 1}`);
        if (prevStep) {
          prevStep.classList.remove('active');
          prevStep.classList.add('completed');
        }

        // Activate current step
        const currStep = document.getElementById(`step-${currentStep}`);
        if (currStep) {
          currStep.classList.add('active');
        }

        // Update progress bar
        const progress = (currentStep / totalSteps) * 100;
        const progressBar = document.getElementById('tracker-progress');
        if (progressBar) {
          progressBar.style.width = progress + '%';
        }

        // Update ETA countdown
        const eta = 10 - (currentStep * 2.5);
        const etaElement = document.getElementById('delivery-eta');
        const liveEtaElement = document.getElementById('live-eta');

        if (etaElement) {
          etaElement.textContent = Math.ceil(eta) + ' mins';
        }
        if (liveEtaElement) {
          liveEtaElement.textContent = Math.ceil(eta);
        }

        currentStep++;
      }, stepDuration);
    }

    // Update delivery ETA countdown
    function startETACountdown(minutes) {
      let seconds = minutes * 60;

      const countdown = setInterval(() => {
        seconds--;

        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;

        const etaElement = document.getElementById('delivery-eta');
        const liveEtaElement = document.getElementById('live-eta');

        if (etaElement) {
          etaElement.textContent = mins + ' mins ' + secs + ' secs';
        }
        if (liveEtaElement) {
          liveEtaElement.textContent = mins;
        }

        if (seconds <= 0) {
          clearInterval(countdown);
          // Show delivery completed notification
          if (etaElement) {
            etaElement.textContent = 'Arriving now!';
          }
        }
      }, 1000);
    }

    // Check if it's peak hour for surge pricing
    function checkPeakHourSurge() {
      const hour = new Date().getHours();
      const surgePricingElement = document.getElementById('express-surge-pricing');

      // Peak hours: 12-2 PM and 7-10 PM
      const isPeakHour = (hour >= 12 && hour <= 14) || (hour >= 19 && hour <= 22);

      if (surgePricingElement) {
        surgePricingElement.style.display = isPeakHour ? 'flex' : 'none';
      }

      return isPeakHour;
    }

    // Initialize peak hour check on page load
    document.addEventListener('DOMContentLoaded', function() {
      checkPeakHourSurge();
    });

    // Address Selection
    function selectAddress(index, address) {
      // Remove selected class from all
      document.querySelectorAll('.address-card').forEach(card => {
        card.classList.remove('selected');
      });

      // Add selected class to clicked
      event.currentTarget.classList.add('selected');

      // Check the radio button
      document.getElementById('addr-' + index).checked = true;

      selectedAddressIndex = index;
    }

    // Toggle address form
    function toggleAddressForm() {
      const form = document.getElementById('new-address-form');
      if (form.style.display === 'none') {
        form.style.display = 'block';
        console.log('Address form opened, checking map initialization');

        // Initialize map when form is opened
        if (typeof google !== 'undefined' && google.maps && !map) {
          console.log('Initializing map for new address form');
          setTimeout(initMap, 100);
        } else if (!map) {
          console.log('Waiting for Google Maps to load before initializing map');
          // Wait for Google Maps to be ready
          const checkGoogleMaps = setInterval(() => {
            if (typeof google !== 'undefined' && google.maps) {
              clearInterval(checkGoogleMaps);
              console.log('Google Maps ready, initializing map now');
              initMap();
            }
          }, 200);

          // Clear interval after 5 seconds to prevent infinite loop
          setTimeout(() => clearInterval(checkGoogleMaps), 5000);
        }
      } else {
        form.style.display = 'none';
      }
    }

    function saveNewAddress() {
      const address = document.getElementById('new_address').value;
      const city = document.getElementById('city').value;
      const state = document.getElementById('state').value;
      const pincode = document.getElementById('pincode').value;

      if (!address || !city || !state || !pincode) {
        alert('Please fill all address fields');
        return;
      }

      // Hide the form
      toggleAddressForm();

      // Continue to payment
      goToPayment();
    }

    // Payment Selection
    function selectPayment(method) {
      // Remove selected class from all
      document.querySelectorAll('.payment-option').forEach(option => {
        option.classList.remove('selected');
      });

      // Add selected class to clicked
      event.currentTarget.classList.add('selected');

      // Update radio button
      document.getElementById(method).checked = true;

      // Update button text
      const btnText = document.getElementById('btn-text');
      if (method === 'razorpay') {
        btnText.textContent = 'Pay with Razorpay';
      } else {
        btnText.textContent = 'Place Order (COD)';
      }
    }

    // Place Order
    document.getElementById('place-order-btn').addEventListener('click', function() {
      const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

      if (paymentMethod === 'razorpay') {
        initiateRazorpayPayment();
      } else {
        // For COD, submit the form
        document.getElementById('loading-overlay').classList.add('active');
        document.getElementById('checkout-form').submit();
      }
    });

    function initiateRazorpayPayment() {
      const placeOrderBtn = document.getElementById('place-order-btn');
      const btnText = document.getElementById('btn-text');

      placeOrderBtn.disabled = true;
      btnText.textContent = 'Processing...';

      const formData = new FormData(document.getElementById('checkout-form'));



      fetch('{{ route("payment.createOrder") }}', {
          method: 'POST',
          body: formData,
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          }
        })
        .then(response => {
          console.log('Payment API Response Status:', response.status);
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
          }
          return response.json();
        })
        .then(data => {
          console.log('Payment API Response Data:', data);

          if (data.error) {
            console.error('Payment API Error:', data.error);
            alert(data.error);
            placeOrderBtn.disabled = false;
            btnText.textContent = 'Pay with Razorpay';
            return;
          }

          if (!data.success || !data.order_id) {
            console.error('Invalid payment response:', data);
            alert('Invalid payment response. Please try again.');
            placeOrderBtn.disabled = false;
            btnText.textContent = 'Pay with Razorpay';
            return;
          }

          // Get Razorpay key from API response (more reliable than config)
          const razorpayKey = data.key || '{{ config("services.razorpay.key") }}';
          if (!razorpayKey || razorpayKey === '') {
            console.error('Razorpay key not configured');
            alert('Payment system not configured. Please contact support.');
            placeOrderBtn.disabled = false;
            btnText.textContent = 'Pay with Razorpay';
            return;
          }

          console.log('Initializing Razorpay with:', {
            key: razorpayKey.substring(0, 15) + '...', // Log partial key for security
            amount: data.amount,
            order_id: data.order_id
          });

          const options = {
            key: razorpayKey,
            amount: data.amount,
            currency: data.currency,
            name: data.name,
            description: data.description,
            order_id: data.order_id,
            prefill: data.prefill,
            theme: {
              color: '#667eea'
            },
            handler: function(response) {
              verifyPayment(response);
            },
            modal: {
              ondismiss: function() {
                placeOrderBtn.disabled = false;
                btnText.textContent = 'Pay with Razorpay';
              }
            }
          };

          try {
            // Check if Razorpay is loaded
            if (typeof Razorpay === 'undefined') {
              throw new Error('Razorpay SDK not loaded. Please refresh the page and try again.');
            }

            const rzp = new Razorpay(options);

            // Add error handler for Razorpay
            rzp.on('payment.failed', function(response) {
              console.error('Razorpay payment failed:', response.error);
              alert('Payment failed: ' + response.error.description);
              placeOrderBtn.disabled = false;
              btnText.textContent = 'Pay with Razorpay';
            });

            rzp.open();
          } catch (razorpayError) {
            console.error('Razorpay initialization error:', razorpayError);
            alert('Payment system error: ' + razorpayError.message);
            placeOrderBtn.disabled = false;
            btnText.textContent = 'Pay with Razorpay';
          }
        })
        .catch(error => {
          console.error('Payment initialization error:', error);
          let errorMessage = 'Payment initialization failed. ';

          if (error.message.includes('HTTP 5')) {
            errorMessage += 'Server error. Please try again in a moment.';
          } else if (error.message.includes('HTTP 4')) {
            errorMessage += 'Please check your information and try again.';
          } else if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
            errorMessage += 'Network error. Please check your connection and try again.';
          } else {
            errorMessage += 'Please try again or contact support.';
          }

          alert(errorMessage);
          placeOrderBtn.disabled = false;
          btnText.textContent = 'Pay with Razorpay';
        });
    }

    function verifyPayment(paymentData) {
      document.getElementById('loading-overlay').classList.add('active');

      fetch('{{ route("payment.verify") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify(paymentData)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(data.message);
            window.location.href = data.redirect;
          } else {
            alert(data.error || 'Payment verification failed');
            document.getElementById('loading-overlay').classList.remove('active');
            placeOrderBtn.disabled = false;
            btnText.textContent = 'Pay with Razorpay';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Payment verification failed. Please contact support.');
          document.getElementById('loading-overlay').classList.remove('active');
          placeOrderBtn.disabled = false;
          btnText.textContent = 'Pay with Razorpay';
        });
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>