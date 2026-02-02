@extends('delivery_partner.layout')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Welcome Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Welcome back, {{ $partner->full_name }}!</h4>
                    <small class="text-muted">Let's make some deliveries today</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-{{ $partner->is_online ? 'success' : 'secondary' }} mb-1 d-block">
                        {{ $partner->is_online ? 'Online' : 'Offline' }}
                    </span>
                    <small class="text-muted">{{ $partner->status }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Balance Card -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="text-white-50 mb-1">Current Balance</h6>
                            <h2 class="mb-0">
                                <i class="bi bi-currency-rupee"></i>{{ number_format($stats['wallet_balance'], 2) }}
                            </h2>
                            <small class="text-white-75">
                                Total Earned: ₹{{ number_format($stats['total_earnings'], 2) }}
                            </small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-wallet2 fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Stats -->
    <div class="row mb-3">
        <div class="col-6">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center py-3">
                    <i class="bi bi-truck fs-2 mb-1"></i>
                    <h5 class="mb-0">{{ $stats['today_deliveries'] }}</h5>
                    <small>Today's Deliveries</small>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center py-3">
                    <i class="bi bi-currency-rupee fs-2 mb-1"></i>
                    <h5 class="mb-0">{{ number_format($stats['today_earnings'], 2) }}</h5>
                    <small>Today's Earnings</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Stats -->
    <div class="row mb-3">
        <div class="col-4">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <h5 class="text-primary mb-0">{{ $stats['completed_orders'] }}</h5>
                    <small class="text-muted">Total Orders</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <h5 class="text-warning mb-0">{{ $stats['completion_rate'] }}%</h5>
                    <small class="text-muted">Success Rate</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center h-100">
                <div class="card-body py-3">
                    <h5 class="text-info mb-0">{{ $stats['rating'] }}</h5>
                    <small class="text-muted">Rating</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-lightning-charge"></i>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('delivery-partner.requests.index') }}" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i>
                                Find Orders
                            </a>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-primary w-100" onclick="updateLocation()">
                                <i class="bi bi-geo-alt"></i>
                                Update Location
                            </button>
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <button class="btn btn-outline-success w-100" onclick="toggleOnlineStatus()">
                                <i class="bi bi-power"></i>
                                Go {{ $partner->is_online ? 'Offline' : 'Online' }}
                            </button>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('delivery-partner.profile') }}" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-person"></i>
                                Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-clock-history"></i>
                        Recent Activity
                    </h6>
                    <a href="{{ route('delivery-partner.requests.index') }}" class="btn btn-outline-primary btn-sm">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($recentOrders->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-1 opacity-50"></i>
                            <p class="mt-2">No recent activity</p>
                            <small>Start accepting delivery requests to see your activity here</small>
                        </div>
                    @else
                        @foreach($recentOrders->take(3) as $request)
                            <div class="border-bottom p-3">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h6 class="mb-1">
                                            Order #{{ $request->order_id ?? 'N/A' }}
                                            <span class="badge bg-{{ $request->status_color }} badge-sm ms-2">
                                                {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                            </span>
                                        </h6>
                                        <p class="mb-0 small text-muted">
                                            <i class="bi bi-geo-alt"></i>
                                            {{ Str::limit($request->delivery_address, 30) }}
                                        </p>
                                        <small class="text-muted">{{ $request->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="col-4 text-end">
                                        <h6 class="text-success mb-0">
                                            ₹{{ number_format($request->delivery_fee, 2) }}
                                        </h6>
                                        @if($request->status === 'accepted' || $request->status === 'picked_up')
                                            <a href="{{ route('delivery-partner.requests.show', $request) }}" 
                                               class="btn btn-outline-primary btn-sm">
                                                Continue
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Nearby Orders -->
    @if($availableOrders->isNotEmpty())
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="bi bi-geo-alt"></i>
                        Orders Near You ({{ $availableOrders->count() }})
                    </h6>
                </div>
                <div class="card-body p-0">
                    @foreach($availableOrders->take(2) as $order)
                        <div class="border-bottom p-3">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h6 class="text-success mb-1">₹25.00</h6>
                                    <p class="mb-0 small">
                                        <i class="bi bi-geo-alt text-danger"></i>
                                        Pickup nearby
                                    </p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> Est. 15-20 min
                                    </small>
                                </div>
                                <div class="col-4 text-end">
                                    <a href="{{ route('delivery-partner.requests.index') }}" 
                                       class="btn btn-success btn-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Notifications -->
    @if($notifications->isNotEmpty())
    <div class="row">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-bell"></i>
                        Notifications
                    </h6>
                </div>
                <div class="card-body p-0">
                    @foreach($notifications->take(3) as $notification)
                        <div class="border-bottom p-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-{{ $notification['icon'] }} text-{{ $notification['type'] }} me-2 mt-1"></i>
                                <div>
                                    <p class="mb-1">{{ $notification['message'] }}</p>
                                    <small class="text-muted">{{ $notification['time'] }}</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Location Modal -->
<div class="modal fade" id="locationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Your Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Update your location to receive nearby delivery requests.</p>
                <div class="d-grid">
                    <button class="btn btn-primary" onclick="getCurrentLocation()">
                        <i class="bi bi-geo-alt"></i> Use Current Location
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function updateLocation() {
    const modal = new bootstrap.Modal(document.getElementById('locationModal'));
    modal.show();
}

function getCurrentLocation() {
    if (!navigator.geolocation) {
        showToast('Geolocation is not supported by this browser', 'error');
        return;
    }

    showToast('Getting your location...', 'info');
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            fetch('/delivery-partner/update-location', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    latitude: lat,
                    longitude: lng
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Location updated successfully!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('locationModal')).hide();
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to update location', 'error');
            });
        },
        function(error) {
            let message = 'Location access denied';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Location access denied. Please enable location permission.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location information unavailable.';
                    break;
                case error.TIMEOUT:
                    message = 'Location request timed out.';
                    break;
            }
            showToast(message, 'error');
        }
    );
}

function toggleOnlineStatus() {
    fetch('/delivery-partner/toggle-online', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to update status', 'error');
    });
}

function showToast(message, type) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    // Create toast
    const toastId = 'toast-' + Date.now();
    const bgColor = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-primary';
    
    const toastHtml = `
        <div id="${toastId}" class="toast ${bgColor} text-white" role="alert">
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove toast after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// Auto-refresh dashboard every 60 seconds
setInterval(function() {
    if (document.visibilityState === 'visible') {
        window.location.reload();
    }
}, 60000);
</script>
@endsection

@section('styles')
<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.text-white-50 {
    color: rgba(255, 255, 255, 0.5) !important;
}

.text-white-75 {
    color: rgba(255, 255, 255, 0.75) !important;
}

.badge-sm {
    font-size: 0.7rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header h6 {
    font-weight: 600;
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }
}
</style>
@endsection