<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Partners Management - Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .card { border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .table-responsive { border-radius: 10px; background: white; }
        .badge { padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle me-2"></i>Validation Errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3">
                        <i class="fas fa-users text-primary me-2"></i>All Delivery Partners
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                            <li class="breadcrumb-item active">Delivery Partners</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('admin.delivery-partners.dashboard') }}" class="btn btn-info">
                    <i class="fas fa-chart-line me-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by name/phone/email" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="is_online" class="form-control">
                                <option value="">All Online Status</option>
                                <option value="1" {{ request('is_online') === '1' ? 'selected' : '' }}>Online</option>
                                <option value="0" {{ request('is_online') === '0' ? 'selected' : '' }}>Offline</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="is_available" class="form-control">
                                <option value="">All Availability</option>
                                <option value="1" {{ request('is_available') === '1' ? 'selected' : '' }}>Available</option>
                                <option value="0" {{ request('is_available') === '0' ? 'selected' : '' }}>Busy</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Partners Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if($partners->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Online</th>
                                        <th>Available</th>
                                        <th>Rating</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($partners as $partner)
                                        <tr>
                                            <td><strong>{{ $partner->name }}</strong></td>
                                            <td>{{ $partner->phone }}</td>
                                            <td>{{ $partner->email }}</td>
                                            <td>
                                                <span class="badge bg-{{ 
                                                    $partner->status === 'approved' ? 'success' : 
                                                    ($partner->status === 'pending' ? 'warning' : 
                                                    ($partner->status === 'suspended' ? 'danger' : 
                                                    ($partner->status === 'rejected' ? 'dark' : 'secondary'))) 
                                                }}">
                                                    {{ ucfirst($partner->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($partner->is_online)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-circle"></i> Online
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">Offline</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($partner->is_available)
                                                    <span class="badge bg-info">Available</span>
                                                @else
                                                    <span class="badge bg-danger">Busy</span>
                                                @endif
                                            </td>
                                            <td>
                                                <i class="fas fa-star text-warning"></i> {{ $partner->rating ?? 0 }}/5
                                            </td>
                                            <td>{{ $partner->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.delivery-partners.show', $partner->id) }}" 
                                                       class="btn btn-sm btn-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    @if($partner->status === 'pending')
                                                        <!-- Quick approve for pending partners -->
                                                        <form method="POST" action="{{ route('admin.delivery-partners.approve', $partner->id) }}" 
                                                              style="display: inline;" onsubmit="return confirm('Approve this delivery partner?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                                data-bs-target="#rejectModal{{ $partner->id }}" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @elseif($partner->status === 'approved')
                                                        <!-- Block button for approved partners -->
                                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                                data-bs-target="#blockModal{{ $partner->id }}" title="Suspend">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    @elseif($partner->status === 'suspended')
                                                        <!-- Unblock button for suspended partners -->
                                                        <form method="POST" action="{{ route('admin.delivery-partners.unblock', $partner->id) }}" 
                                                              style="display: inline;" onsubmit="return confirm('Reactivate this delivery partner?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success" title="Reactivate">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                            data-bs-target="#statusModal{{ $partner->id }}" title="Update Status">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Status Update Modal -->
                                        <div class="modal fade" id="statusModal{{ $partner->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Status - {{ $partner->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="{{ route('admin.delivery-partners.update-status', $partner->id) }}">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="form-group mb-3">
                                                                <label class="form-label">Status</label>
                                                                <select name="status" class="form-control" required>
                                                                    <option value="pending" {{ $partner->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                                    <option value="approved" {{ $partner->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                                                    <option value="rejected" {{ $partner->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                                    <option value="suspended" {{ $partner->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                                                    <option value="inactive" {{ $partner->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                                </select>
                                                                <small class="form-text text-muted mt-1 d-block">
                                                                    <strong>Pending:</strong> Awaiting approval<br>
                                                                    <strong>Approved:</strong> Can accept deliveries<br>
                                                                    <strong>Rejected:</strong> Application denied<br>
                                                                    <strong>Suspended:</strong> Temporarily blocked<br>
                                                                    <strong>Inactive:</strong> Not currently working
                                                                </small>
                                                            </div>
                                                            
                                                            <div class="form-group" id="reasonField{{ $partner->id }}" style="display: none;">
                                                                <label class="form-label">Reason (optional)</label>
                                                                <textarea name="reason" class="form-control" rows="3" 
                                                                    placeholder="Enter reason for status change..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="fas fa-save me-2"></i>Update Status
                                                            </button>
                                                        </div>
                                                    </form>
                                                    
                                                    <script>
                                                        // Show reason field for rejected/suspended status
                                                        document.querySelector('#statusModal{{ $partner->id }} select[name="status"]').addEventListener('change', function(e) {
                                                            const reasonField = document.getElementById('reasonField{{ $partner->id }}');
                                                            if (e.target.value === 'rejected' || e.target.value === 'suspended') {
                                                                reasonField.style.display = 'block';
                                                            } else {
                                                                reasonField.style.display = 'none';
                                                            }
                                                        });
                                                    </script>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal{{ $partner->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-times-circle me-2"></i>Reject Application - {{ $partner->name }}
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="{{ route('admin.delivery-partners.reject', $partner->id) }}">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                This will reject the delivery partner application.
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Reason for Rejection *</label>
                                                                <textarea name="reason" class="form-control" rows="4" required
                                                                    placeholder="Enter the reason for rejecting this application..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-times me-2"></i>Reject Application
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Block/Suspend Modal -->
                                        <div class="modal fade" id="blockModal{{ $partner->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-warning">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-ban me-2"></i>Suspend Partner - {{ $partner->name }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="{{ route('admin.delivery-partners.block', $partner->id) }}">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                This will temporarily suspend the delivery partner. They won't be able to accept new deliveries.
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="form-label">Reason for Suspension *</label>
                                                                <textarea name="reason" class="form-control" rows="4" required
                                                                    placeholder="Enter the reason for suspending this partner..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="fas fa-ban me-2"></i>Suspend Partner
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $partners->links() }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No delivery partners found.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>