<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Partner Details - {{ $partner->name }} - Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .card { border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .border-left-primary { border-left: 4px solid #007bff; }
        .border-left-success { border-left: 4px solid #28a745; }
        .border-left-warning { border-left: 4px solid #ffc107; }
        .border-left-info { border-left: 4px solid #17a2b8; }
        .badge { padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">
                    <i class="fas fa-user text-primary me-2"></i>{{ $partner->name }}
                </h1>
                <a href="{{ route('admin.delivery-partners.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="text-primary text-uppercase mb-1">Total Deliveries</div>
                    <div class="h3 mb-0">{{ $stats['total_deliveries'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="text-success text-uppercase mb-1">Completed</div>
                    <div class="h3 mb-0">{{ $stats['completed_deliveries'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="text-warning text-uppercase mb-1">Pending</div>
                    <div class="h3 mb-0">{{ $stats['pending_deliveries'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="text-info text-uppercase mb-1">Completion Rate</div>
                    <div class="h3 mb-0">{{ $stats['completion_rate'] }}%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Partner Info -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-6">Status:</dt>
                        <dd class="col-sm-6">
                            <span class="badge bg-{{ $partner->status === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($partner->status) }}
                            </span>
                        </dd>

                        <dt class="col-sm-6">Phone:</dt>
                        <dd class="col-sm-6">{{ $partner->phone }}</dd>

                        <dt class="col-sm-6">Email:</dt>
                        <dd class="col-sm-6">{{ $partner->email }}</dd>

                        <dt class="col-sm-6">Online:</dt>
                        <dd class="col-sm-6">
                            @if($partner->is_online)
                                <span class="badge bg-success">Online</span>
                            @else
                                <span class="badge bg-secondary">Offline</span>
                            @endif
                        </dd>

                        <dt class="col-sm-6">Available:</dt>
                        <dd class="col-sm-6">
                            @if($partner->is_available)
                                <span class="badge bg-info">Available</span>
                            @else
                                <span class="badge bg-danger">Busy</span>
                            @endif
                        </dd>

                        <dt class="col-sm-6">Rating:</dt>
                        <dd class="col-sm-6">
                            <i class="fas fa-star text-warning"></i> {{ $partner->rating ?? 0 }}/5
                        </dd>

                        <dt class="col-sm-6">Joined:</dt>
                        <dd class="col-sm-6">{{ $partner->created_at->format('M d, Y') }}</dd>
                    </dl>
                </div>
            </div>

            <!-- Performance -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Performance</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-6">Today:</dt>
                        <dd class="col-sm-6">{{ $stats['today_deliveries'] }} deliveries</dd>

                        <dt class="col-sm-6">Today Earnings:</dt>
                        <dd class="col-sm-6">₹{{ $stats['today_earnings'] }}</dd>

                        <dt class="col-sm-6">Total Earnings:</dt>
                        <dd class="col-sm-6">₹{{ $stats['total_earnings'] }}</dd>

                        <dt class="col-sm-6">Avg Rating:</dt>
                        <dd class="col-sm-6">{{ $stats['avg_rating'] }}/5</dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Job Assignment & Actions -->
        <div class="col-md-8">
            <!-- Assign Job -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Assign Delivery Job</h5>
                </div>
                <div class="card-body">
                    @if($availableOrders->count() > 0)
                        <form method="POST" action="{{ route('admin.delivery-partners.assign-job', $partner->id) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-8">
                                    <select name="order_id" class="form-control" required>
                                        <option value="">Select an order to assign</option>
                                        @foreach($availableOrders as $order)
                                            <option value="{{ $order->id }}">
                                                #{{ $order->order_number }} - ₹{{ $order->total_amount }} ({{ $order->user->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-check me-2"></i>Assign Job
                                    </button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Add notes (optional)..."></textarea>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No available orders for assignment at the moment.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Current Deliveries -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Current Deliveries ({{ $stats['pending_deliveries'] }})</h5>
                </div>
                <div class="card-body">
                    @if($partner->deliveryRequests()->whereIn('status', ['accepted', 'picked_up'])->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Assigned At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($partner->deliveryRequests()->whereIn('status', ['accepted', 'picked_up'])->get() as $delivery)
                                        <tr>
                                            <td>#{{ $delivery->order->order_number ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $delivery->status === 'picked_up' ? 'primary' : 'info' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                                                </span>
                                            </td>
                                            <td>{{ $delivery->order->user->name ?? 'N/A' }}</td>
                                            <td>₹{{ $delivery->order->total_amount ?? 0 }}</td>
                                            <td>{{ $delivery->assigned_at?->diffForHumans() ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No active deliveries.</p>
                    @endif
                </div>
            </div>

            <!-- Send Notification -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Send Notification</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.delivery-partners.send-notification', $partner->id) }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Notification title" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Message</label>
                            <textarea name="message" class="form-control" rows="3" placeholder="Notification message" required></textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label>Type</label>
                            <select name="type" class="form-control" required>
                                <option value="info">Info</option>
                                <option value="success">Success</option>
                                <option value="warning">Warning</option>
                                <option value="error">Error</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-bell me-2"></i>Send Notification
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>