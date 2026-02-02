<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track {{ $deliveryPartner->name }} - Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .card { border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .badge { padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-map-marker-alt text-primary me-2"></i>Track {{ $deliveryPartner->name }}
            </h1>
        </div>
    </div>

    <div class="row">
        <!-- Real-time Map -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Live Location</h5>
                </div>
                <div class="card-body">
                    <div id="map" style="height: 500px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <p class="text-muted">
                            <i class="fas fa-map me-2"></i>Map will load here
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Partner Status -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Current Status</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-6">Online:</dt>
                        <dd class="col-sm-6">
                            @if($deliveryPartner->is_online)
                                <span class="badge bg-success">
                                    <i class="fas fa-circle"></i> Online
                                </span>
                            @else
                                <span class="badge bg-secondary">Offline</span>
                            @endif
                        </dd>

                        <dt class="col-sm-6">Available:</dt>
                        <dd class="col-sm-6">
                            @if($deliveryPartner->is_available)
                                <span class="badge bg-info">Available</span>
                            @else
                                <span class="badge bg-danger">Busy</span>
                            @endif
                        </dd>

                        <dt class="col-sm-6">Last Active:</dt>
                        <dd class="col-sm-6">{{ $deliveryPartner->last_active_at?->diffForHumans() ?? 'Never' }}</dd>

                        <dt class="col-sm-6">Rating:</dt>
                        <dd class="col-sm-6">
                            <i class="fas fa-star text-warning"></i> {{ $deliveryPartner->rating ?? 0 }}/5
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Current Delivery -->
            @if($currentDeliveries->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current Delivery</h5>
                    </div>
                    <div class="card-body">
                        @foreach($currentDeliveries as $delivery)
                            <div class="mb-3 pb-3 border-bottom">
                                <h6><strong>Order #{{ $delivery->order->order_number ?? 'N/A' }}</strong></h6>
                                <p class="mb-1">
                                    <small class="text-muted">Customer:</small><br>
                                    {{ $delivery->order->user->name ?? 'N/A' }}
                                </p>
                                <p class="mb-1">
                                    <small class="text-muted">Address:</small><br>
                                    {{ $delivery->order->delivery_address ?? 'N/A' }}
                                </p>
                                <p class="mb-1">
                                    <small class="text-muted">Status:</small><br>
                                    <span class="badge bg-{{ $delivery->status === 'picked_up' ? 'primary' : 'info' }}">
                                        {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                                    </span>
                                </p>
                                <p class="mb-0">
                                    <small class="text-muted">Amount:</small><br>
                                    <strong>₹{{ $delivery->order->total_amount ?? 0 }}</strong>
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- History -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Today's Delivery History</h5>
                </div>
                <div class="card-body">
                    @if($locationHistory->count() > 0)
                        <div class="timeline">
                            @foreach($locationHistory as $entry)
                                <div class="timeline-item">
                                    <div class="timeline-marker">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">
                                            Order #{{ $entry->order->order_number ?? 'N/A' }}
                                            <small class="text-muted">- {{ $entry->order->user->name ?? 'Customer' }}</small>
                                        </h6>
                                        <p class="text-muted mb-1">{{ $entry->order->delivery_address ?? 'Address N/A' }}</p>
                                        <small class="text-muted">
                                            <i class="far fa-clock"></i>
                                            Delivered at {{ $entry->completed_at?->format('H:i A') ?? 'N/A' }}
                                        </small>
                                        <p class="mb-0 mt-2">
                                            <span class="badge bg-success">₹{{ $entry->delivery_fee ?? 0 }}</span>
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No delivery history for today.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 20px;
        border-left: 2px solid #e9ecef;
        padding-left: 20px;
    }

    .timeline-item:last-child {
        border-left: none;
    }

    .timeline-marker {
        position: absolute;
        left: -17px;
        width: 30px;
        height: 30px;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .timeline-content {
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
    }
</style>

<script>
    // Real-time tracking update every 10 seconds
    setInterval(function() {
        // In a production app, you would fetch updated location data from an API
        // For now, this is a placeholder for the tracking logic
        console.log('Checking for delivery partner location updates...');
    }, 10000);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
