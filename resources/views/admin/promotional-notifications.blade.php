<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Promotional Notifications - Admin Panel</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .notification-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .notification-card .card-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .btn-notification {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-notification:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
            color: white;
        }
        .btn-automated {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            margin: 5px;
            transition: all 0.3s ease;
        }
        .btn-automated:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
            color: white;
        }
        .quick-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-bell"></i> Promotional Notifications</h1>
                    <p class="mb-0">Send promotional notifications to your customers</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ url('/admin/dashboard') }}" class="btn btn-light me-2">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="{{ url('/admin/orders') }}" class="btn btn-outline-light">
                        <i class="fas fa-list"></i> View Orders
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> 
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="row text-center">
                <div class="col-md-3">
                    <h3>{{ \App\Models\User::where('role', 'buyer')->count() }}</h3>
                    <p>Total Buyers</p>
                </div>
                <div class="col-md-3">
                    <h3>{{ \App\Models\User::where('role', 'seller')->count() }}</h3>
                    <p>Total Sellers</p>
                </div>
                <div class="col-md-3">
                    <h3>{{ \App\Models\Product::where('discount', '>', 0)->count() }}</h3>
                    <p>Products on Sale</p>
                </div>
                <div class="col-md-3">
                    <h3>{{ \App\Models\Notification::where('created_at', '>=', now()->today())->count() }}</h3>
                    <p>Notifications Today</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Custom Promotional Message -->
            <div class="col-md-6">
                <div class="notification-card card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-bullhorn"></i> Custom Promotional Message</h4>
                    </div>
                    <div class="card-body">
                        <form action="/admin/send-promotional-notification" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="title" class="form-label">Notification Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       placeholder="e.g., 🔥 Special Offer - 50% Off!" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="4" 
                                          placeholder="Enter your promotional message..." required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="user_type" class="form-label">Target Audience</label>
                                <select class="form-select" id="user_type" name="user_type" required>
                                    <option value="all">All Users</option>
                                    <option value="buyers">Buyers Only</option>
                                    <option value="sellers">Sellers Only</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="send_email" name="send_email" value="1">
                                    <label class="form-check-label" for="send_email">
                                        📧 Also send via Email
                                    </label>
                                    <small class="form-text text-muted d-block">Send beautifully designed promotional emails along with in-app notifications</small>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-notification w-100">
                                <i class="fas fa-paper-plane"></i> Send Notification
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Automated Notifications -->
            <div class="col-md-6">
                <div class="notification-card card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-robot"></i> Automated Notifications</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Send pre-configured promotional notifications like Amazon</p>
                        
                        <form action="/admin/send-automated-notifications" method="POST" class="d-grid gap-3">
                            @csrf
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="send_email_auto" name="send_email" value="1">
                                    <label class="form-check-label" for="send_email_auto">
                                        📧 Send via Email (Recommended)
                                    </label>
                                    <small class="form-text text-muted d-block">Send beautiful promotional emails like Amazon</small>
                                </div>
                            </div>
                            
                            <button type="submit" name="notification_type" value="daily_deals" class="btn btn-automated">
                                <i class="fas fa-fire"></i> Daily Deals (50% Off)
                            </button>
                            
                            <button type="submit" name="notification_type" value="weekly_newsletter" class="btn btn-automated">
                                <i class="fas fa-newspaper"></i> Weekly Newsletter
                            </button>
                            
                            <button type="submit" name="notification_type" value="flash_sale" class="btn btn-automated">
                                <i class="fas fa-bolt"></i> Flash Sale Alert (2 Hours)
                            </button>

                            <button type="submit" name="notification_type" value="weekend_special" class="btn btn-automated">
                                <i class="fas fa-calendar-weekend"></i> Weekend Special Deals
                            </button>
                            
                            <button type="submit" name="notification_type" value="wishlist_sale" class="btn btn-automated">
                                <i class="fas fa-heart"></i> Wishlist Items on Sale
                            </button>
                            
                            <button type="submit" name="notification_type" value="back_in_stock" class="btn btn-automated">
                                <i class="fas fa-boxes"></i> Back in Stock Alerts
                            </button>
                        </form>

                        <hr class="my-4">

                        <!-- Quick Email Campaigns -->
                        <div class="text-center">
                            <h6>⚡ Quick Email Campaigns</h6>
                            <form action="/admin/send-bulk-promotional-email" method="POST" class="d-grid gap-2 mt-3">
                                @csrf
                                <button type="submit" name="email_type" value="daily_deals" class="btn btn-outline-primary btn-sm">
                                    📧 Send Daily Deals Email to All Buyers
                                </button>
                                <button type="submit" name="email_type" value="flash_sale" class="btn btn-outline-danger btn-sm">
                                    📧 Send Flash Sale Email to All Buyers
                                </button>
                            </form>
                        </div>

                        <hr class="my-4">
                        
                        <div class="text-center">
                            <h6>💡 Pro Tip</h6>
                            <p class="small text-muted">
                                These automated notifications are designed to mimic Amazon's notification system. 
                                They include emojis, urgency, and personalized content based on user behavior.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="notification-card card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-history"></i> Recent Notifications</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Recipients</th>
                                        <th>Sent At</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $recentNotifications = \App\Models\Notification::select('type', 'title', 'created_at')
                                                             ->selectRaw('COUNT(*) as recipient_count')
                                                             ->where('created_at', '>=', now()->subDays(7))
                                                             ->groupBy('type', 'title', 'created_at')
                                                             ->orderBy('created_at', 'desc')
                                                             ->limit(10)
                                                             ->get();
                                    @endphp
                                    
                                    @forelse($recentNotifications as $notification)
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $notification->type)) }}</span>
                                            </td>
                                            <td>{{ $notification->title }}</td>
                                            <td>
                                                <i class="fas fa-users"></i> {{ $notification->recipient_count }} users
                                            </td>
                                            <td>{{ $notification->created_at->format('M d, Y H:i') }}</td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Sent
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                <i class="fas fa-bell-slash"></i> No notifications sent in the last 7 days
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>