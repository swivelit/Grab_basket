<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Live Order Tracking - {{ config('app.name') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=places,geometry" async defer></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }

        .tracking-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .tracking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            color: white;
            text-align: center;
        }

        #map {
            width: 100%;
            height: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .order-info-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }

        .status-timeline {
            position: relative;
            padding: 20px 0;
        }

        .status-step {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
        }

        .status-step:last-child {
            margin-bottom: 0;
        }

        .status-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            z-index: 2;
            position: relative;
        }

        .status-icon.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            animation: pulse 2s infinite;
        }

        .status-icon.completed {
            background: #28a745;
            color: white;
        }

        .status-icon.pending {
            background: #e9ecef;
            color: #6c757d;
        }

        .status-line {
            position: absolute;
            left: 24px;
            top: 50px;
            width: 2px;
            height: calc(100% - 50px);
            background: #e9ecef;
        }

        .status-line.active {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }

        .status-content {
            margin-left: 20px;
            flex: 1;
        }

        .delivery-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin: 15px 0;
        }

        .delivery-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .eta-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }

        .info-badge {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
            margin: 5px;
            display: inline-block;
            font-size: 14px;
        }

        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: transform 0.3s;
        }

        .refresh-btn:hover {
            transform: scale(1.1) rotate(180deg);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .contact-btn {
            padding: 10px 25px;
            border-radius: 25px;
            border: none;
            background: #28a745;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .contact-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tracking-container">
            <!-- Header -->
            <div class="tracking-header">
                <h2><i class="bi bi-geo-alt-fill"></i> Live Order Tracking</h2>
                <p class="mb-0">Track your order in real-time</p>
                <div class="live-indicator mt-3">
                    <span class="live-dot"></span>
                    LIVE TRACKING
                </div>
            </div>

            <div class="p-4">
                <!-- Order Selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Select Order to Track:</label>
                    <select id="orderSelect" class="form-select form-select-lg">
                        <option value="">Choose an order...</option>
                        @foreach($orders as $order)
                            <option value="{{ $order->id }}" 
                                data-status="{{ $order->status }}"
                                data-product="{{ $order->product->name ?? 'Product' }}"
                                data-address="{{ $order->delivery_address }}"
                                data-delivery-partner="{{ $order->deliveryPartner->name ?? 'Not assigned' }}"
                                data-delivery-phone="{{ $order->deliveryPartner->phone ?? 'N/A' }}"
                                data-delivery-lat="{{ $order->deliveryPartner->latitude ?? '' }}"
                                data-delivery-lng="{{ $order->deliveryPartner->longitude ?? '' }}"
                                data-buyer-lat="{{ $order->delivery_latitude ?? '' }}"
                                data-buyer-lng="{{ $order->delivery_longitude ?? '' }}">
                                Order #{{ $order->id }} - {{ $order->product->name ?? 'Product' }} - ₹{{ number_format($order->amount, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="trackingDetails" style="display: none;">
                    <!-- Google Map -->
                    <div class="order-info-card">
                        <h5 class="mb-3"><i class="bi bi-map"></i> Live Location</h5>
                        <div id="map"></div>
                        <div class="eta-badge mt-3">
                            <i class="bi bi-clock"></i> ETA: <span id="eta">Calculating...</span>
                        </div>
                    </div>

                    <!-- Delivery Partner Info -->
                    <div id="deliveryPartnerInfo" class="delivery-info" style="display: none;">
                        <div class="delivery-avatar" id="partnerAvatar">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Delivery Partner</h6>
                            <div id="partnerName" class="fw-bold">Not assigned</div>
                            <div id="partnerPhone" class="text-muted small">N/A</div>
                        </div>
                        <button class="contact-btn" onclick="contactDeliveryPartner()">
                            <i class="bi bi-telephone"></i> Call
                        </button>
                    </div>

                    <!-- Order Details -->
                    <div class="order-info-card">
                        <h5 class="mb-3"><i class="bi bi-box-seam"></i> Order Details</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-badge">
                                    <i class="bi bi-cart"></i> Product: <strong id="productName"></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-badge">
                                    <i class="bi bi-cash"></i> Amount: <strong id="orderAmount"></strong>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <div class="info-badge w-100">
                                    <i class="bi bi-geo-alt"></i> Delivery Address: <strong id="deliveryAddress"></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Timeline -->
                    <div class="order-info-card">
                        <h5 class="mb-3"><i class="bi bi-list-check"></i> Order Status</h5>
                        <div class="status-timeline">
                            <div class="status-line" id="statusLine"></div>
                            
                            <div class="status-step" data-status="paid">
                                <div class="status-icon" id="status-paid">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="status-content">
                                    <h6>Order Placed & Paid</h6>
                                    <small class="text-muted">Your order has been confirmed</small>
                                </div>
                            </div>

                            <div class="status-step" data-status="confirmed">
                                <div class="status-icon" id="status-confirmed">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <div class="status-content">
                                    <h6>Order Confirmed</h6>
                                    <small class="text-muted">Seller is preparing your order</small>
                                </div>
                            </div>

                            <div class="status-step" data-status="shipped">
                                <div class="status-icon" id="status-shipped">
                                    <i class="bi bi-truck"></i>
                                </div>
                                <div class="status-content">
                                    <h6>Out for Delivery</h6>
                                    <small class="text-muted">Your order is on the way</small>
                                </div>
                            </div>

                            <div class="status-step" data-status="delivered">
                                <div class="status-icon" id="status-delivered">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                                <div class="status-content">
                                    <h6>Delivered</h6>
                                    <small class="text-muted">Order delivered successfully</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <div class="text-center mt-4">
                    <a href="{{ url('/') }}" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-house"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="refresh-btn" onclick="refreshTracking()" title="Refresh Location">
        <i class="bi bi-arrow-clockwise"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let map, deliveryMarker, buyerMarker, routePath;
        let currentOrderId = null;
        let refreshInterval = null;

        // Initialize when order is selected
        document.getElementById('orderSelect').addEventListener('change', function() {
            const orderId = this.value;
            if (!orderId) {
                document.getElementById('trackingDetails').style.display = 'none';
                return;
            }

            currentOrderId = orderId;
            const option = this.options[this.selectedIndex];
            
            // Show tracking details
            document.getElementById('trackingDetails').style.display = 'block';
            
            // Update order info
            document.getElementById('productName').textContent = option.dataset.product;
            document.getElementById('orderAmount').textContent = '₹' + option.textContent.split('₹')[1];
            document.getElementById('deliveryAddress').textContent = option.dataset.address;

            // Update delivery partner info
            if (option.dataset.deliveryPartner !== 'Not assigned') {
                document.getElementById('deliveryPartnerInfo').style.display = 'flex';
                document.getElementById('partnerName').textContent = option.dataset.deliveryPartner;
                document.getElementById('partnerPhone').textContent = option.dataset.deliveryPhone;
                document.getElementById('partnerAvatar').textContent = option.dataset.deliveryPartner.charAt(0).toUpperCase();
            } else {
                document.getElementById('deliveryPartnerInfo').style.display = 'none';
            }

            // Update status
            updateOrderStatus(option.dataset.status);

            // Initialize map
            initializeMap(option);

            // Start auto-refresh
            startAutoRefresh();
        });

        function initializeMap(option) {
            // Default location (India center)
            const defaultCenter = { lat: 20.5937, lng: 78.9629 };
            
            // Initialize map
            if (!map) {
                map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 13,
                    center: defaultCenter,
                    mapTypeControl: false,
                    fullscreenControl: true,
                    streetViewControl: false,
                });
            }

            // Clear existing markers and paths
            if (deliveryMarker) deliveryMarker.setMap(null);
            if (buyerMarker) buyerMarker.setMap(null);
            if (routePath) routePath.setMap(null);

            // Get coordinates
            const deliveryLat = parseFloat(option.dataset.deliveryLat);
            const deliveryLng = parseFloat(option.dataset.deliveryLng);
            const buyerLat = parseFloat(option.dataset.buyerLat);
            const buyerLng = parseFloat(option.dataset.buyerLng);

            // Buyer location marker
            if (buyerLat && buyerLng) {
                buyerMarker = new google.maps.Marker({
                    position: { lat: buyerLat, lng: buyerLng },
                    map: map,
                    title: 'Delivery Location',
                    icon: {
                        url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
                    }
                });

                map.setCenter({ lat: buyerLat, lng: buyerLng });

                // Add info window
                const buyerInfo = new google.maps.InfoWindow({
                    content: '<div style="padding: 10px;"><strong>Delivery Address</strong><br>' + option.dataset.address + '</div>'
                });
                buyerMarker.addListener('click', () => buyerInfo.open(map, buyerMarker));
            }

            // Delivery partner location marker
            if (deliveryLat && deliveryLng) {
                deliveryMarker = new google.maps.Marker({
                    position: { lat: deliveryLat, lng: deliveryLng },
                    map: map,
                    title: 'Delivery Partner',
                    icon: {
                        url: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                    },
                    animation: google.maps.Animation.BOUNCE
                });

                // Add info window
                const partnerInfo = new google.maps.InfoWindow({
                    content: '<div style="padding: 10px;"><strong>' + option.dataset.deliveryPartner + '</strong><br>Delivery Partner<br>' + option.dataset.deliveryPhone + '</div>'
                });
                deliveryMarker.addListener('click', () => partnerInfo.open(map, deliveryMarker));

                // Draw route if both locations exist
                if (buyerLat && buyerLng) {
                    drawRoute(
                        { lat: deliveryLat, lng: deliveryLng },
                        { lat: buyerLat, lng: buyerLng }
                    );
                }
            }
        }

        function drawRoute(start, end) {
            // Draw polyline
            routePath = new google.maps.Polyline({
                path: [start, end],
                geodesic: true,
                strokeColor: '#667eea',
                strokeOpacity: 0.8,
                strokeWeight: 4,
                map: map
            });

            // Calculate distance and ETA
            const distance = google.maps.geometry.spherical.computeDistanceBetween(
                new google.maps.LatLng(start.lat, start.lng),
                new google.maps.LatLng(end.lat, end.lng)
            );

            const distanceKm = (distance / 1000).toFixed(2);
            const etaMinutes = Math.ceil(distanceKm * 3); // Assuming 3 min per km

            document.getElementById('eta').textContent = `${etaMinutes} minutes (${distanceKm} km away)`;

            // Fit bounds to show both markers
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(start);
            bounds.extend(end);
            map.fitBounds(bounds);
        }

        function updateOrderStatus(status) {
            // Reset all icons
            document.querySelectorAll('.status-icon').forEach(icon => {
                icon.classList.remove('active', 'completed');
                icon.classList.add('pending');
            });

            const statusMap = {
                'paid': 1,
                'confirmed': 2,
                'shipped': 3,
                'delivered': 4
            };

            const currentLevel = statusMap[status] || 0;

            // Update completed steps
            for (let i = 1; i <= currentLevel; i++) {
                const statusKey = Object.keys(statusMap).find(key => statusMap[key] === i);
                const icon = document.getElementById('status-' + statusKey);
                if (icon) {
                    icon.classList.remove('pending');
                    if (i === currentLevel) {
                        icon.classList.add('active');
                    } else {
                        icon.classList.add('completed');
                    }
                }
            }
        }

        function refreshTracking() {
            if (!currentOrderId) return;

            // Animate refresh button
            const btn = document.querySelector('.refresh-btn i');
            btn.style.animation = 'none';
            setTimeout(() => {
                btn.style.animation = '';
            }, 10);

            // Reload order data via AJAX
            fetch(`/api/orders/${currentOrderId}/location`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update delivery partner location
                    if (deliveryMarker && data.delivery_lat && data.delivery_lng) {
                        const newPos = { lat: parseFloat(data.delivery_lat), lng: parseFloat(data.delivery_lng) };
                        deliveryMarker.setPosition(newPos);

                        // Redraw route
                        if (buyerMarker) {
                            const buyerPos = buyerMarker.getPosition();
                            if (routePath) routePath.setMap(null);
                            drawRoute(newPos, { lat: buyerPos.lat(), lng: buyerPos.lng() });
                        }
                    }

                    // Update status
                    if (data.status) {
                        updateOrderStatus(data.status);
                    }
                }
            })
            .catch(error => console.error('Error refreshing location:', error));
        }

        function startAutoRefresh() {
            // Clear existing interval
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }

            // Refresh every 30 seconds
            refreshInterval = setInterval(refreshTracking, 30000);
        }

        function contactDeliveryPartner() {
            const phone = document.getElementById('partnerPhone').textContent;
            if (phone && phone !== 'N/A') {
                window.location.href = 'tel:' + phone;
            } else {
                alert('Delivery partner phone number not available');
            }
        }

        // Cleanup on page leave
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>
