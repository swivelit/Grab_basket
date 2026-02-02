@extends('delivery_partner.layout')

@section('title', 'Delivery Details')

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('delivery-partner.requests.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h5 class="d-inline mb-0">Delivery Request #{{ $deliveryRequest->id }}</h5>
                </div>
                <span class="badge bg-{{ $deliveryRequest->status_color }} fs-6">
                    {{ ucfirst(str_replace('_', ' ', $deliveryRequest->status)) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Delivery Fee Card -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-3">
                    <h2 class="mb-0">
                        <i class="bi bi-currency-rupee"></i>{{ number_format($deliveryRequest->delivery_fee, 2) }}
                    </h2>
                    <p class="mb-0 opacity-75">Delivery Earning</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Information -->
    @if($deliveryRequest->order)
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-bag-check"></i>
                        Order Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Order ID</small>
                            <p class="mb-2 fw-bold">#{{ $deliveryRequest->order->id }}</p>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Total Amount</small>
                            <p class="mb-2 fw-bold">₹{{ number_format($deliveryRequest->order->total_amount ?? 0, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Pickup Location -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-geo-alt"></i>
                        Pickup Location
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">{{ $deliveryRequest->pickup_address }}</p>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Coordinates</small>
                            <p class="mb-0 small">{{ $deliveryRequest->pickup_latitude }}, {{ $deliveryRequest->pickup_longitude }}</p>
                        </div>
                        <div class="col-6 text-end">
                            <a href="https://maps.google.com/?q={{ $deliveryRequest->pickup_latitude }},{{ $deliveryRequest->pickup_longitude }}" 
                               target="_blank" 
                               class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-map"></i> View on Maps
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Location -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-geo-alt-fill"></i>
                        Delivery Location
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">{{ $deliveryRequest->delivery_address }}</p>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Coordinates</small>
                            <p class="mb-0 small">{{ $deliveryRequest->delivery_latitude }}, {{ $deliveryRequest->delivery_longitude }}</p>
                        </div>
                        <div class="col-6 text-end">
                            <a href="https://maps.google.com/?q={{ $deliveryRequest->delivery_latitude }},{{ $deliveryRequest->delivery_longitude }}" 
                               target="_blank" 
                               class="btn btn-outline-success btn-sm">
                                <i class="bi bi-map"></i> View on Maps
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Information -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle"></i>
                        Delivery Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Distance</small>
                            <p class="mb-2">{{ number_format($deliveryRequest->distance_km, 1) }} km</p>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Est. Time</small>
                            <p class="mb-2">{{ $deliveryRequest->estimated_time_minutes }} minutes</p>
                        </div>
                    </div>
                    @if($deliveryRequest->notes)
                    <div class="mt-2">
                        <small class="text-muted">Special Notes</small>
                        <p class="mb-0">{{ $deliveryRequest->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-clock-history"></i>
                        Delivery Timeline
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item {{ $deliveryRequest->requested_at ? 'completed' : '' }}">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Request Created</h6>
                                <small class="text-muted">
                                    @if($deliveryRequest->requested_at)
                                        {{ $deliveryRequest->requested_at->format('M d, Y h:i A') }}
                                    @else
                                        Pending
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div class="timeline-item {{ $deliveryRequest->accepted_at ? 'completed' : '' }}">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Request Accepted</h6>
                                <small class="text-muted">
                                    @if($deliveryRequest->accepted_at)
                                        {{ $deliveryRequest->accepted_at->format('M d, Y h:i A') }}
                                    @else
                                        Pending
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div class="timeline-item {{ $deliveryRequest->pickup_at ? 'completed' : '' }}">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Order Picked Up</h6>
                                <small class="text-muted">
                                    @if($deliveryRequest->pickup_at)
                                        {{ $deliveryRequest->pickup_at->format('M d, Y h:i A') }}
                                    @else
                                        Pending
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div class="timeline-item {{ $deliveryRequest->delivered_at ? 'completed' : '' }}">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Delivery Completed</h6>
                                <small class="text-muted">
                                    @if($deliveryRequest->delivered_at)
                                        {{ $deliveryRequest->delivered_at->format('M d, Y h:i A') }}
                                    @else
                                        Pending
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    @if($deliveryRequest->delivery_partner_id === Auth::guard('delivery_partner')->id())
    <div class="row">
        <div class="col-12">
            @if($deliveryRequest->status === 'pending')
                <button class="btn btn-success w-100 mb-2" onclick="acceptRequest()">
                    <i class="bi bi-check-circle"></i>
                    Accept Delivery Request
                </button>
            @elseif($deliveryRequest->status === 'accepted')
                <button class="btn btn-warning w-100 mb-2" onclick="markPickup()">
                    <i class="bi bi-box-seam"></i>
                    Mark as Picked Up
                </button>
                <button class="btn btn-outline-danger w-100 mb-2" onclick="showCancelModal()">
                    <i class="bi bi-x-circle"></i>
                    Cancel Request
                </button>
            @elseif($deliveryRequest->status === 'picked_up')
                <button class="btn btn-success w-100 mb-2" onclick="completeDelivery()">
                    <i class="bi bi-check2-all"></i>
                    Complete Delivery (Get ₹25)
                </button>
                <button class="btn btn-outline-danger w-100 mb-2" onclick="showCancelModal()">
                    <i class="bi bi-x-circle"></i>
                    Cancel Request
                </button>
            @endif
        </div>
    </div>
    @endif
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Delivery Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="cancelForm">
                    <div class="mb-3">
                        <label class="form-label">Reason for cancellation *</label>
                        <select class="form-select" name="reason" required>
                            <option value="">Select reason</option>
                            <option value="Vehicle breakdown">Vehicle breakdown</option>
                            <option value="Emergency">Personal emergency</option>
                            <option value="Traffic issues">Traffic/road issues</option>
                            <option value="Customer unavailable">Customer unavailable</option>
                            <option value="Wrong address">Wrong pickup/delivery address</option>
                            <option value="Other">Other reason</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" onclick="cancelRequest()">Cancel Request</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function acceptRequest() {
    const button = event.target;
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Accepting...';

    fetch(`/delivery-partner/requests/{{ $deliveryRequest->id }}/accept`, {
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
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-check-circle"></i> Accept Delivery Request';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to accept request', 'error');
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-check-circle"></i> Accept Delivery Request';
    });
}

function markPickup() {
    const button = event.target;
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';

    fetch(`/delivery-partner/requests/{{ $deliveryRequest->id }}/pickup`, {
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
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-box-seam"></i> Mark as Picked Up';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to mark pickup', 'error');
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-box-seam"></i> Mark as Picked Up';
    });
}

function completeDelivery() {
    const button = event.target;
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';

    fetch(`/delivery-partner/requests/{{ $deliveryRequest->id }}/complete`, {
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
                }, 2000);
            }
        } else {
            showToast(data.message, 'error');
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-check2-all"></i> Complete Delivery (Get ₹25)';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to complete delivery', 'error');
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-check2-all"></i> Complete Delivery (Get ₹25)';
    });
}

function showCancelModal() {
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

function cancelRequest() {
    const form = document.getElementById('cancelForm');
    const formData = new FormData(form);
    const reason = formData.get('reason');

    if (!reason) {
        showToast('Please select a reason for cancellation', 'error');
        return;
    }

    fetch(`/delivery-partner/requests/{{ $deliveryRequest->id }}/cancel`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('cancelModal')).hide();
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            }
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to cancel request', 'error');
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
</script>
@endsection

@section('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 20px;
    width: 2px;
    height: calc(100% - 10px);
    background-color: #dee2e6;
}

.timeline-item.completed:not(:last-child)::before {
    background-color: #28a745;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid #ffffff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-item.completed .timeline-marker {
    box-shadow: 0 0 0 2px #28a745;
}

.timeline-content h6 {
    margin-bottom: 4px;
    font-size: 0.9rem;
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