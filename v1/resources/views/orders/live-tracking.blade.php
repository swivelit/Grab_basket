@extends('layouts.app')

@section('title', 'Live Order Tracking - 10 Minute Delivery')

@section('head')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&callback=initMap" async defer></script>
<style>
  :root {
    --zepto-green: #0C831F;
    --blinkit-yellow: #F8CB46;
    --express-red: #FF3B3B;
  }

  .tracking-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
  }

  .tracking-header {
    background: linear-gradient(135deg, var(--zepto-green), #0A6917);
    color: white;
    padding: 30px;
    border-radius: 20px;
    margin-bottom: 30px;
    box-shadow: 0 10px 40px rgba(12, 131, 31, 0.3);
  }

  .eta-badge {
    display: inline-block;
    background: var(--blinkit-yellow);
    color: #000;
    padding: 12px 24px;
    border-radius: 30px;
    font-size: 1.5rem;
    font-weight: 900;
    box-shadow: 0 5px 20px rgba(248, 203, 70, 0.4);
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% {
      transform: scale(1);
    }
    50% {
      transform: scale(1.05);
    }
  }

  .map-container {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
  }

  #map {
    height: 500px;
    width: 100%;
    border-radius: 16px;
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .delivery-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }

  .info-card {
    background: white;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s, box-shadow 0.3s;
  }

  .info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  }

  .info-card-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 15px;
  }

  .delivery-partner-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .order-details-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
  }

  .address-card {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
  }

  .timeline {
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
  }

  .timeline-step {
    display: flex;
    align-items: flex-start;
    margin-bottom: 30px;
    position: relative;
  }

  .timeline-step:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 50px;
    width: 2px;
    height: calc(100% + 10px);
    background: linear-gradient(180deg, var(--zepto-green), #e0e0e0);
  }

  .timeline-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-right: 20px;
    flex-shrink: 0;
    z-index: 1;
  }

  .timeline-step.completed .timeline-icon {
    background: var(--zepto-green);
    color: white;
    box-shadow: 0 4px 15px rgba(12, 131, 31, 0.3);
  }

  .timeline-step.active .timeline-icon {
    background: var(--blinkit-yellow);
    color: #000;
    animation: pulse 2s infinite;
  }

  .timeline-step.pending .timeline-icon {
    background: #e0e0e0;
    color: #999;
  }

  .live-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--express-red);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
  }

  .live-dot {
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    animation: blink 1.5s infinite;
  }

  @keyframes blink {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.3;
    }
  }

  .refresh-btn {
    background: var(--zepto-green);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 30px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(12, 131, 31, 0.3);
  }

  .refresh-btn:hover {
    background: #0A6917;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(12, 131, 31, 0.4);
  }

  .contact-partner-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 30px;
    font-weight: 700;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
  }

  .contact-partner-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: white;
  }

  @media (max-width: 768px) {
    #map {
      height: 350px;
    }

    .tracking-header {
      padding: 20px;
    }

    .eta-badge {
      font-size: 1.2rem;
      padding: 10px 20px;
    }

    .delivery-info-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
@endsection

@section('content')
<div class="tracking-container">
  <!-- Header with ETA -->
  <div class="tracking-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="live-indicator mb-3">
          <span class="live-dot"></span>
          LIVE TRACKING
        </div>
        <h2 class="mb-2">
          @if($order->delivery_type === 'express_10min')
            ⚡ 10-Minute Express Delivery
          @else
            🚚 Standard Delivery
          @endif
        </h2>
        <p class="mb-0 opacity-90">Order #{{ $order->id }} • {{ $order->created_at->format('d M Y, h:i A') }}</p>
      </div>
      <div class="text-end">
        <div class="eta-badge">
          ⏰ <span id="etaMinutes">{{ $order->eta_minutes ?? 'N/A' }}</span> mins
        </div>
        <button class="refresh-btn mt-3" onclick="refreshTracking()">
          <i class="bi bi-arrow-clockwise"></i> Refresh Location
        </button>
      </div>
    </div>
  </div>

  <!-- Google Maps -->
  <div class="map-container">
    <div id="map"></div>
  </div>

  <!-- Delivery Information Cards -->
  <div class="delivery-info-grid">
    <!-- Delivery Partner -->
    @if($order->delivery_partner_name)
    <div class="info-card delivery-partner-card">
      <div class="info-card-icon" style="background: rgba(255,255,255,0.2);">
        🛵
      </div>
      <h5 class="mb-3">Your Delivery Partner</h5>
      <h4 class="mb-2">{{ $order->delivery_partner_name }}</h4>
      <p class="mb-2 opacity-90">
        <i class="bi bi-telephone-fill"></i> {{ $order->delivery_partner_phone }}
      </p>
      <p class="mb-3 opacity-90">
        <i class="bi bi-truck"></i> {{ $order->delivery_partner_vehicle }}
      </p>
      <a href="tel:{{ $order->delivery_partner_phone }}" class="contact-partner-btn">
        <i class="bi bi-telephone-fill"></i> Call Partner
      </a>
    </div>
    @endif

    <!-- Order Details -->
    <div class="info-card order-details-card">
      <div class="info-card-icon" style="background: rgba(255,255,255,0.2);">
        📦
      </div>
      <h5 class="mb-3">Order Details</h5>
      <p class="mb-2"><strong>Order ID:</strong> #{{ $order->id }}</p>
      <p class="mb-2"><strong>Items:</strong> {{ $order->quantity }} item(s)</p>
      <p class="mb-2"><strong>Total:</strong> ₹{{ number_format($order->total_amount, 2) }}</p>
      <p class="mb-2"><strong>Status:</strong> 
        <span class="badge bg-light text-dark">{{ ucfirst($order->status) }}</span>
      </p>
      @if($order->distance_km)
      <p class="mb-0"><strong>Distance:</strong> {{ $order->distance_km }} km</p>
      @endif
    </div>

    <!-- Delivery Address -->
    <div class="info-card address-card">
      <div class="info-card-icon" style="background: rgba(255,255,255,0.2);">
        📍
      </div>
      <h5 class="mb-3">Delivery Address</h5>
      <p class="mb-2">{{ $order->delivery_address }}</p>
      <p class="mb-2">{{ $order->delivery_city }}, {{ $order->delivery_state }}</p>
      <p class="mb-3">{{ $order->delivery_pincode }}</p>
      @if($order->delivery_notes)
      <p class="mb-0 opacity-90">
        <i class="bi bi-info-circle"></i> {{ $order->delivery_notes }}
      </p>
      @endif
    </div>
  </div>

  <!-- Delivery Timeline -->
  <div class="timeline">
    <h4 class="mb-4">📍 Delivery Progress</h4>

    <div class="timeline-step {{ $order->status === 'confirmed' || $order->status === 'shipped' || $order->status === 'delivered' ? 'completed' : 'pending' }}">
      <div class="timeline-icon">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <div>
        <h6 class="mb-1">Order Confirmed</h6>
        <p class="text-muted mb-0">Your order has been confirmed and is being prepared</p>
        @if($order->created_at)
        <small class="text-muted">{{ $order->created_at->format('h:i A') }}</small>
        @endif
      </div>
    </div>

    <div class="timeline-step {{ $order->delivery_started_at ? 'completed' : ($order->status === 'shipped' ? 'active' : 'pending') }}">
      <div class="timeline-icon">
        <i class="bi bi-truck"></i>
      </div>
      <div>
        <h6 class="mb-1">Out for Delivery</h6>
        <p class="text-muted mb-0">Your delivery partner is on the way</p>
        @if($order->delivery_started_at)
        <small class="text-muted">{{ $order->delivery_started_at->format('h:i A') }}</small>
        @endif
      </div>
    </div>

    <div class="timeline-step {{ $order->status === 'delivered' ? 'completed' : 'pending' }}">
      <div class="timeline-icon">
        <i class="bi bi-house-check-fill"></i>
      </div>
      <div>
        <h6 class="mb-1">Delivered</h6>
        <p class="text-muted mb-0">Package delivered successfully</p>
        @if($order->delivery_completed_at)
        <small class="text-muted">{{ $order->delivery_completed_at->format('h:i A') }}</small>
        @endif
      </div>
    </div>
  </div>
</div>

<script>
  let map;
  let deliveryMarker;
  let storeMarker;
  let customerMarker;
  let routePath;

  const orderData = {
    id: {{ $order->id }},
    deliveryLat: {{ $order->delivery_latitude ?? 'null' }},
    deliveryLng: {{ $order->delivery_longitude ?? 'null' }},
    storeLat: {{ $order->store_latitude ?? 'null' }},
    storeLng: {{ $order->store_longitude ?? 'null' }},
    customerLat: {{ $order->customer_latitude ?? 'null' }},
    customerLng: {{ $order->customer_longitude ?? 'null' }},
  };

  function initMap() {
    // Default center (customer location or fallback)
    const centerLat = orderData.customerLat || 12.9716;
    const centerLng = orderData.customerLng || 77.5946;

    map = new google.maps.Map(document.getElementById('map'), {
      zoom: 14,
      center: { lat: centerLat, lng: centerLng },
      styles: [
        {
          featureType: 'poi',
          elementType: 'labels',
          stylers: [{ visibility: 'off' }]
        }
      ]
    });

    // Add customer marker (destination)
    if (orderData.customerLat && orderData.customerLng) {
      customerMarker = new google.maps.Marker({
        position: { lat: orderData.customerLat, lng: orderData.customerLng },
        map: map,
        title: 'Your Location',
        icon: {
          url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="60" viewBox="0 0 40 60">
              <path fill="#FF3B3B" d="M20,0 C8.95,0 0,8.95 0,20 C0,35 20,60 20,60 C20,60 40,35 40,20 C40,8.95 31.05,0 20,0 Z"/>
              <circle fill="white" cx="20" cy="20" r="10"/>
            </svg>
          `),
          scaledSize: new google.maps.Size(40, 60)
        }
      });
    }

    // Add store marker (origin)
    if (orderData.storeLat && orderData.storeLng) {
      storeMarker = new google.maps.Marker({
        position: { lat: orderData.storeLat, lng: orderData.storeLng },
        map: map,
        title: 'Store Location',
        icon: {
          url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="60" viewBox="0 0 40 60">
              <path fill="#0C831F" d="M20,0 C8.95,0 0,8.95 0,20 C0,35 20,60 20,60 C20,60 40,35 40,20 C40,8.95 31.05,0 20,0 Z"/>
              <text x="20" y="28" text-anchor="middle" fill="white" font-size="24" font-weight="bold">🏪</text>
            </svg>
          `),
          scaledSize: new google.maps.Size(40, 60)
        }
      });
    }

    // Add delivery partner marker (moving)
    if (orderData.deliveryLat && orderData.deliveryLng) {
      deliveryMarker = new google.maps.Marker({
        position: { lat: orderData.deliveryLat, lng: orderData.deliveryLng },
        map: map,
        title: 'Delivery Partner',
        animation: google.maps.Animation.BOUNCE,
        icon: {
          url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">
              <circle fill="#F8CB46" cx="25" cy="25" r="24" stroke="#000" stroke-width="2"/>
              <text x="25" y="35" text-anchor="middle" fill="#000" font-size="28" font-weight="bold">🛵</text>
            </svg>
          `),
          scaledSize: new google.maps.Size(50, 50)
        }
      });

      // Stop bouncing after 3 seconds
      setTimeout(() => {
        if (deliveryMarker) {
          deliveryMarker.setAnimation(null);
        }
      }, 3000);
    }

    // Draw route if we have all coordinates
    if (orderData.deliveryLat && orderData.customerLat) {
      drawRoute();
    }

    // Fit map to show all markers
    fitMapToMarkers();
  }

  function drawRoute() {
    const directionsService = new google.maps.DirectionsService();
    const directionsRenderer = new google.maps.DirectionsRenderer({
      map: map,
      suppressMarkers: true,
      polylineOptions: {
        strokeColor: '#0C831F',
        strokeWeight: 5,
        strokeOpacity: 0.8
      }
    });

    const request = {
      origin: { lat: orderData.deliveryLat, lng: orderData.deliveryLng },
      destination: { lat: orderData.customerLat, lng: orderData.customerLng },
      travelMode: 'DRIVING'
    };

    directionsService.route(request, (result, status) => {
      if (status === 'OK') {
        directionsRenderer.setDirections(result);
      }
    });
  }

  function fitMapToMarkers() {
    const bounds = new google.maps.LatLngBounds();
    
    if (customerMarker) bounds.extend(customerMarker.getPosition());
    if (storeMarker) bounds.extend(storeMarker.getPosition());
    if (deliveryMarker) bounds.extend(deliveryMarker.getPosition());

    map.fitBounds(bounds);
  }

  function refreshTracking() {
    // Simulate location update (in production, fetch from API)
    fetch(`/api/order/{{ $order->id }}/track`)
      .then(response => response.json())
      .then(data => {
        if (data.latitude && data.longitude) {
          const newPosition = { lat: data.latitude, lng: data.longitude };
          deliveryMarker.setPosition(newPosition);
          
          // Update ETA
          if (data.eta_minutes) {
            document.getElementById('etaMinutes').textContent = data.eta_minutes;
          }

          // Redraw route
          drawRoute();
        }
      })
      .catch(error => console.error('Error refreshing tracking:', error));
  }

  // Auto-refresh every 30 seconds
  setInterval(refreshTracking, 30000);
</script>
@endsection
