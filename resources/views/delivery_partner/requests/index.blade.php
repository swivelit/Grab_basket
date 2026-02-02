@extends('delivery_partner.layout')

@section('title', 'Delivery Requests')

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-truck text-primary"></i>
                    Delivery Requests
                </h4>
                <button class="btn btn-outline-primary btn-sm" onclick="updateLocation()">
                    <i class="bi bi-geo-alt"></i> Update Location
                </button>
            </div>
        </div>
    </div>

    <!-- Nearby Requests -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="bi bi-lightning-charge"></i>
                        Nearby Delivery Requests ({{ $nearbyRequests->count() }})
                    </h6>
                </div>
                <div class="card-body p-0">
                    @if($nearbyRequests->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-search fs-1 opacity-50"></i>
                            <p class="mt-2">No nearby delivery requests available</p>
                            <small>We'll notify you when new requests come in your area</small>
                        </div>
                    @else
                        @foreach($nearbyRequests as $request)
                            <div class="border-bottom p-3 request-item" data-id="{{ $request->id }}">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h6 class="text-success mb-1">
                                            <i class="bi bi-currency-rupee"></i>{{ number_format($request->delivery_fee, 2) }}
                                        </h6>
                                        <p class="mb-1 small">
                                            <i class="bi bi-geo-alt text-danger"></i>
                                            {{ Str::limit($request->pickup_address, 40) }}
                                        </p>
                                        <p class="mb-1 small">
                                            <i class="bi bi-geo-alt-fill text-success"></i>
                                            {{ Str::limit($request->delivery_address, 40) }}
                                        </p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> {{ $request->estimated_time_minutes }} min
                                            • <i class="bi bi-speedometer2"></i> {{ number_format($request->distance_km, 1) }} km
                                        </small>
                                    </div>
                                    <div class="col-4 text-end">
                                        <button class="btn btn-success btn-sm w-100 accept-request" 
                                                data-id="{{ $request->id }}">
                                            <i class="bi bi-check-circle"></i>
                                            Accept
                                        </button>
                                        <small class="text-muted d-block mt-1">
                                            {{ $request->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Accepted Requests -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-check-circle"></i>
                        My Active Deliveries ({{ $acceptedRequests->count() }})
                    </h6>
                </div>
                <div class="card-body p-0">
                    @if($acceptedRequests->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-truck fs-1 opacity-50"></i>
                            <p class="mt-2">No active deliveries</p>
                        </div>
                    @else
                        @foreach($acceptedRequests as $request)
                            <div class="border-bottom p-3">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <div class="d-flex align-items-center mb-1">
                                            <h6 class="text-success mb-0 me-2">
                                                <i class="bi bi-currency-rupee"></i>{{ number_format($request->delivery_fee, 2) }}
                                            </h6>
                                            <span class="badge bg-{{ $request->status_color }} badge-sm">
                                                {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                            </span>
                                        </div>
                                        <p class="mb-1 small">
                                            <i class="bi bi-geo-alt text-danger"></i>
                                            {{ Str::limit($request->pickup_address, 35) }}
                                        </p>
                                        <p class="mb-1 small">
                                            <i class="bi bi-geo-alt-fill text-success"></i>
                                            {{ Str::limit($request->delivery_address, 35) }}
                                        </p>
                                    </div>
                                    <div class="col-4 text-end">
                                        <a href="{{ route('delivery-partner.requests.show', $request) }}" 
                                           class="btn btn-outline-primary btn-sm w-100">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Completions -->
    <div class="row">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-check2-all"></i>
                        Recent Completions ({{ $completedRequests->count() }})
                    </h6>
                </div>
                <div class="card-body p-0">
                    @if($completedRequests->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-check fs-1 opacity-50"></i>
                            <p class="mt-2">No completed deliveries yet</p>
                        </div>
                    @else
                        @foreach($completedRequests->take(5) as $request)
                            <div class="border-bottom p-3">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h6 class="text-success mb-1">
                                            <i class="bi bi-currency-rupee"></i>{{ number_format($request->delivery_fee, 2) }}
                                            <small class="text-muted">earned</small>
                                        </h6>
                                        <p class="mb-0 small text-muted">
                                            Completed {{ $request->delivered_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="col-4 text-end">
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i>
                                            Completed
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
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
                <p class="text-muted">We need your current location to show you nearby delivery requests.</p>
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
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh every 30 seconds for new requests
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            window.location.reload();
        }
    }, 30000);

    // Handle accept request buttons
    document.querySelectorAll('.accept-request').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.id;
            acceptRequest(requestId);
        });
    });
});

function acceptRequest(requestId) {
    const button = document.querySelector(`[data-id="${requestId}"]`);
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Accepting...';

    fetch(`/delivery-partner/requests/${requestId}/accept`, {
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
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            }
        } else {
            showToast(data.message, 'error');
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-check-circle"></i> Accept';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to accept request', 'error');
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-check-circle"></i> Accept';
    });
}

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
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
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
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        }
    );
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
</script>
@endsection

@section('styles')
<style>
.request-item:hover {
    background-color: #f8f9fa;
}

.card-header h6 {
    font-weight: 600;
}

.badge-sm {
    font-size: 0.7rem;
}

.toast-container {
    z-index: 1055;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }
}
</style>
@endsection