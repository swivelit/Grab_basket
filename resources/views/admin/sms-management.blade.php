<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Management - Admin Dashboard</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 250px;
            background-color: #1e1e2f;
            color: #fff;
            padding-top: 20px;
            z-index: 1000;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .sidebar .logo {
            background-color: #1a1a2e;
            margin-top: -40px;
        }

        .sidebar .nav-link {
            color: #bdc3c7;
            margin: 8px 15px;
            padding: 12px 20px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #3498db;
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            font-size: 1.1rem;
            width: 20px;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .sms-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 2rem;
        }

        .sms-card h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .btn-sms {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-sms:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .character-count {
            font-size: 0.875rem;
            color: #6c757d;
            text-align: right;
            margin-top: 0.5rem;
        }

        .character-count.warning {
            color: #f39c12;
        }

        .character-count.danger {
            color: #e74c3c;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo p-4 text-center">
            <h4 style="color: #3498db; font-weight: bold;">📱 SMS Manager</h4>
        </div>
        <ul class="nav flex-column">
            <li><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a class="nav-link" href="{{ route('admin.orders') }}"><i class="bi bi-cart-check"></i> Orders</a></li>
            <li><a class="nav-link" href="{{ route('admin.manageuser') }}"><i class="bi bi-people"></i> Users</a></li>
            <li><a class="nav-link active" href="{{ route('admin.sms.dashboard') }}"><i class="bi bi-chat-dots"></i> SMS Management</a></li>
            <li><a class="nav-link" href="{{ route('admin.promotional.form') }}"><i class="bi bi-bell-fill"></i> Email Notifications</a></li>
            <li><a class="nav-link" href="{{ route('tracking.form') }}"><i class="bi bi-truck"></i> Track Package</a></li>
            <li><a class="nav-link text-danger" href="{{ route('admin.logout') }}"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-6 fw-bold text-dark">📱 SMS Management Dashboard</h1>
                <p class="lead text-muted">Manage SMS communications with your customers</p>
            </div>
        </div>

        <!-- Demo Mode Warning -->
        @if(isset($accountStatus['is_demo']) && $accountStatus['is_demo'])
        <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-start">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2">⚠️ Infobip Demo Mode Active</h5>
                    <p class="mb-2">Your account is in demo mode with ${{ $accountStatus['balance'] }} {{ $accountStatus['currency'] }} balance. SMS will only be delivered to <strong>whitelisted numbers</strong>.</p>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <p class="mb-2"><strong>To receive SMS notifications:</strong></p>
                            <ol class="small mb-3">
                                <li>Login to <a href="https://portal.infobip.com" target="_blank">Infobip Portal</a></li>
                                <li>Navigate to <strong>SMS → Demo numbers</strong> or <strong>Channels → SMS</strong></li>
                                <li>Add phone numbers to whitelist (format: <code>+917010299714</code>)</li>
                                <li>OR add credits to your account for unlimited SMS</li>
                            </ol>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid gap-2">
                                <a href="https://portal.infobip.com" target="_blank" class="btn btn-warning btn-sm">
                                    <i class="bi bi-box-arrow-up-right"></i> Open Infobip Portal
                                </a>
                                <a href="https://www.infobip.com/contact" target="_blank" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-headset"></i> Contact Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <h3>{{ $stats['total_buyers'] }}</h3>
                    <p>Buyers with Phone</p>
                    <i class="bi bi-people-fill" style="font-size: 2rem; opacity: 0.8;"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <h3>{{ $stats['total_sellers'] }}</h3>
                    <p>Sellers with Phone</p>
                    <i class="bi bi-shop" style="font-size: 2rem; opacity: 0.8;"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <h3>{{ $stats['pending_orders'] }}</h3>
                    <p>Pending Orders</p>
                    <i class="bi bi-clock" style="font-size: 2rem; opacity: 0.8;"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <h3>{{ $stats['shipped_orders'] }}</h3>
                    <p>Shipped Orders</p>
                    <i class="bi bi-truck" style="font-size: 2rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>

        <!-- SMS Actions -->
        <div class="row">
            <!-- Test SMS -->
            <div class="col-md-6 mb-4">
                <div class="sms-card">
                    <h5><i class="bi bi-lightning"></i> Test SMS Configuration</h5>
                    <form action="{{ route('admin.sms.test') }}" method="POST" class="mb-3">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Test Phone Number</label>
                            <input type="text" name="test_phone" class="form-control" placeholder="+91XXXXXXXXXX" required>
                            <small class="form-text text-muted">Enter your phone number to test SMS configuration</small>
                        </div>
                        <button type="submit" class="btn btn-sms">
                            <i class="bi bi-lightning"></i> Send Test SMS
                        </button>
                    </form>
                    
                    <!-- Test with Current Sellers -->
                    <div class="border-top pt-3">
                        <h6 class="text-primary">Test with Current Sellers</h6>
                        <p class="text-muted small">Send test notifications to all sellers in your database</p>
                        <form action="{{ route('admin.sms.test.sellers') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary" onclick="return confirm('This will send test SMS to all sellers with phone numbers. Continue?')">
                                <i class="bi bi-people"></i> Test with Current Sellers
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Bulk Promotional SMS -->
            <div class="col-md-6 mb-4">
                <div class="sms-card">
                    <h5><i class="bi bi-megaphone"></i> Send Promotional SMS</h5>
                    <form action="{{ route('admin.sms.bulk') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Target Audience</label>
                            <select name="target_audience" class="form-select" required>
                                <option value="">Select Audience</option>
                                <option value="buyers">Buyers Only</option>
                                <option value="sellers">Sellers Only</option>
                                <option value="all">All Customers</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="3" maxlength="160" placeholder="Enter your promotional message..." required id="promoMessage"></textarea>
                            <div class="character-count" id="promoCharCount">0/160 characters</div>
                        </div>
                        <button type="submit" class="btn btn-sms">
                            <i class="bi bi-megaphone"></i> Send Bulk SMS
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Reminders -->
            <div class="col-md-6 mb-4">
                <div class="sms-card">
                    <h5><i class="bi bi-clock"></i> Send Order Reminders</h5>
                    <p class="text-muted">Send reminder SMS to customers with pending orders (older than 2 days)</p>
                    <form action="{{ route('admin.sms.reminders') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sms">
                            <i class="bi bi-clock"></i> Send Order Reminders
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-6 mb-4">
                <div class="sms-card">
                    <h5><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="sendWelcomeMessage()">
                            <i class="bi bi-heart"></i> Send Welcome Messages
                        </button>
                        <button class="btn btn-outline-success" onclick="sendPendingOrdersAlert()">
                            <i class="bi bi-exclamation-triangle"></i> Alert Pending Orders
                        </button>
                        <button class="btn btn-outline-info" onclick="sendFeedbackRequest()">
                            <i class="bi bi-star"></i> Request Feedback
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent SMS Activity -->
        <div class="row">
            <div class="col-12">
                <div class="sms-card">
                    <h5><i class="bi bi-activity"></i> SMS Best Practices</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="text-primary">📝 Message Guidelines</h6>
                            <ul class="list-unstyled">
                                <li>✅ Keep messages under 160 characters</li>
                                <li>✅ Include your brand name</li>
                                <li>✅ Add clear call-to-action</li>
                                <li>✅ Use emojis sparingly</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-success">🎯 Targeting Tips</h6>
                            <ul class="list-unstyled">
                                <li>✅ Segment by user behavior</li>
                                <li>✅ Personalize messages</li>
                                <li>✅ Send at optimal times</li>
                                <li>✅ Avoid spam triggers</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-info">📊 Compliance</h6>
                            <ul class="list-unstyled">
                                <li>✅ Include opt-out instructions</li>
                                <li>✅ Respect sending limits</li>
                                <li>✅ Follow DND regulations</li>
                                <li>✅ Monitor delivery rates</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Character counter for promotional message
        document.getElementById('promoMessage').addEventListener('input', function() {
            const message = this.value;
            const charCount = message.length;
            const counter = document.getElementById('promoCharCount');
            
            counter.textContent = `${charCount}/160 characters`;
            
            if (charCount > 140) {
                counter.className = 'character-count danger';
            } else if (charCount > 120) {
                counter.className = 'character-count warning';
            } else {
                counter.className = 'character-count';
            }
        });

        // Quick action functions
        function sendWelcomeMessage() {
            if (confirm('Send welcome SMS to all new users from last 7 days?')) {
                // Implementation for welcome messages
                alert('Welcome messages feature coming soon!');
            }
        }

        function sendPendingOrdersAlert() {
            if (confirm('Send alert SMS to sellers with pending orders?')) {
                // Implementation for pending orders alert
                alert('Pending orders alert feature coming soon!');
            }
        }

        function sendFeedbackRequest() {
            if (confirm('Send feedback request SMS to recent customers?')) {
                // Implementation for feedback request
                alert('Feedback request feature coming soon!');
            }
        }
    </script>
</body>

</html>