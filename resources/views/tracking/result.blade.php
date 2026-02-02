<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Results - {{ $trackingData['tracking_number'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .tracking-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 900px;
        }
        .tracking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .status-card {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .status-card.in-transit {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }
        .status-card.delivered {
            background: linear-gradient(135deg, #4CAF50 0%, #388e3c 100%);
        }
        .status-card.error {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }
        .tracking-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .info-card h6 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .info-card p {
            color: #2c3e50;
            font-weight: 500;
            margin: 0;
            font-size: 1.1rem;
        }
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2.5rem;
            top: 1.5rem;
            width: 1rem;
            height: 1rem;
            background: #667eea;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #667eea;
        }
        .timeline-item.completed::before {
            background: #4CAF50;
            box-shadow: 0 0 0 3px #4CAF50;
        }
        .timeline-date {
            color: #667eea;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .timeline-status {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .timeline-location {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        .progress-bar {
            height: 8px;
            border-radius: 10px;
            background: #e9ecef;
            margin: 1rem 0;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        .refresh-btn {
            background: #f8f9fa;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .refresh-btn:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tracking-container">
            <!-- Header -->
            <div class="tracking-header">
                <h1><i class="fas fa-shipping-fast"></i> Package Tracking</h1>
                <h3>{{ $trackingData['tracking_number'] }}</h3>
                <p class="mb-0">{{ $trackingData['courier'] }}</p>
            </div>

            <!-- Status Card -->
            <div class="status-card {{ strtolower(str_replace(' ', '-', $trackingData['status'])) }}">
                @if($trackingData['error'])
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h3>Tracking Error</h3>
                    <p class="mb-0">{{ $trackingData['error'] }}</p>
                @else
                    @php
                        $statusIcon = 'fas fa-shipping-fast';
                        if (stripos($trackingData['status'], 'delivered') !== false) {
                            $statusIcon = 'fas fa-check-circle';
                        } elseif (stripos($trackingData['status'], 'out for delivery') !== false) {
                            $statusIcon = 'fas fa-truck';
                        } elseif (stripos($trackingData['status'], 'transit') !== false) {
                            $statusIcon = 'fas fa-shipping-fast';
                        }
                    @endphp
                    <i class="{{ $statusIcon }} fa-3x mb-3"></i>
                    <h3>{{ $trackingData['status'] }}</h3>
                    @if($trackingData['current_location'])
                        <p class="mb-0"><i class="fas fa-map-marker-alt"></i> {{ $trackingData['current_location'] }}</p>
                    @endif
                @endif
            </div>

            @if(!$trackingData['error'])
                <!-- Tracking Information -->
                <div class="tracking-info">
                    <div class="info-card">
                        <h6><i class="fas fa-barcode"></i> Tracking Number</h6>
                        <p>{{ $trackingData['tracking_number'] }}</p>
                    </div>
                    <div class="info-card">
                        <h6><i class="fas fa-truck"></i> Courier Service</h6>
                        <p>{{ $trackingData['courier'] }}</p>
                    </div>
                    <div class="info-card">
                        <h6><i class="fas fa-info-circle"></i> Current Status</h6>
                        <p>{{ $trackingData['status'] }}</p>
                    </div>
                    @if($trackingData['estimated_delivery'])
                        <div class="info-card">
                            <h6><i class="fas fa-calendar-alt"></i> Estimated Delivery</h6>
                            <p>{{ date('M d, Y', strtotime($trackingData['estimated_delivery'])) }}</p>
                        </div>
                    @endif
                </div>

                <!-- Progress Bar -->
                @php
                    $progress = 25;
                    if (stripos($trackingData['status'], 'picked') !== false) $progress = 25;
                    elseif (stripos($trackingData['status'], 'transit') !== false) $progress = 50;
                    elseif (stripos($trackingData['status'], 'out for delivery') !== false) $progress = 75;
                    elseif (stripos($trackingData['status'], 'delivered') !== false) $progress = 100;
                @endphp
                <div class="text-center mb-4">
                    <h5>Delivery Progress</h5>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ $progress }}%;"></div>
                    </div>
                    <small class="text-muted">{{ $progress }}% Complete</small>
                </div>

                <!-- Timeline -->
                @if(!empty($trackingData['events']))
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-history"></i> Tracking History</h5>
                            <button class="refresh-btn" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        
                        <div class="timeline">
                            @foreach($trackingData['events'] as $event)
                                <div class="timeline-item completed">
                                    <div class="timeline-date">
                                        {{ date('M d, Y H:i', strtotime($event['date'])) }}
                                    </div>
                                    <div class="timeline-status">
                                        {{ $event['status'] }}
                                    </div>
                                    @if(isset($event['location']) && $event['location'] !== 'Unknown')
                                        <div class="timeline-location">
                                            <i class="fas fa-map-marker-alt"></i> {{ $event['location'] }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Additional Info -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-card">
                            <h6><i class="fas fa-phone"></i> Customer Support</h6>
                            <p>Call: +91-1800-XXX-XXXX<br>
                               Email: support@grabbaskets.com</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <h6><i class="fas fa-shield-alt"></i> Package Security</h6>
                            <p>Your package is insured and tracked<br>
                               24/7 monitoring enabled</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="text-center mt-4">
                <a href="{{ route('tracking.form') }}" class="btn-back me-3">
                    <i class="fas fa-search"></i> Track Another Package
                </a>
                @auth
                    <a href="{{ route('orders.track') }}" class="btn-back">
                        <i class="fas fa-list"></i> My Orders
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes

        // Copy tracking number to clipboard
        function copyTrackingNumber() {
            navigator.clipboard.writeText('{{ $trackingData["tracking_number"] }}');
            alert('Tracking number copied to clipboard!');
        }
    </script>
</body>
</html>