<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Partners Dashboard - Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --info: #118ab2;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            padding: 0;
            overflow-x: hidden;
        }
        
        .dashboard-container {
            padding: 20px;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* Header Styles */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 25px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }
        
        .dashboard-header h2 {
            font-weight: 600;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .breadcrumb {
            background: transparent !important;
            padding: 0 !important;
            margin-top: 8px;
        }
        
        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.8) !important;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .breadcrumb-item a:hover {
            color: white !important;
        }
        
        .breadcrumb-item.active {
            color: white !important;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
        }
        
        .stat-card.total-partners::before { background: var(--primary); }
        .stat-card.online-now::before { background: var(--success); }
        .stat-card.available::before { background: var(--info); }
        .stat-card.pending::before { background: var(--warning); }
        .stat-card.active-deliveries::before { background: var(--secondary); }
        .stat-card.completed-today::before { background: var(--danger); }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .total-partners .stat-icon { background-color: rgba(67, 97, 238, 0.15); color: var(--primary); }
        .online-now .stat-icon { background-color: rgba(6, 214, 160, 0.15); color: var(--success); }
        .available .stat-icon { background-color: rgba(17, 138, 178, 0.15); color: var(--info); }
        .pending .stat-icon { background-color: rgba(255, 209, 102, 0.15); color: var(--warning); }
        .active-deliveries .stat-icon { background-color: rgba(114, 9, 183, 0.15); color: var(--secondary); }
        .completed-today .stat-icon { background-color: rgba(239, 71, 111, 0.15); color: var(--danger); }
        
        .stat-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--gray);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1;
        }
        
        /* Main Content Cards */
        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 22px 28px;
            background: white;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .card-header h5 {
            font-weight: 600;
            color: var(--dark);
            font-size: 1.25rem;
        }
        
        .card-body {
            padding: 0;
        }
        
        /* Table Styles */
        .table-responsive {
            padding: 0 28px 28px;
        }
        
        .table {
            margin-top: 20px;
        }
        
        .table th {
            font-weight: 600;
            color: var(--gray);
            padding: 12px 15px;
            border-top: none;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-top: 1px solid var(--light-gray);
        }
        
        .partner-name {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .status-available {
            background-color: var(--success);
            box-shadow: 0 0 0 3px rgba(6, 214, 160, 0.3);
        }
        
        .status-busy {
            background-color: var(--warning);
            box-shadow: 0 0 0 3px rgba(255, 209, 102, 0.3);
        }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .badge-available {
            background-color: rgba(6, 214, 160, 0.15);
            color: var(--success);
        }
        
        .badge-busy {
            background-color: rgba(255, 209, 102, 0.15);
            color: var(--warning);
        }
        
        .rating {
            color: #ffc107;
            font-weight: 600;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light-gray);
            color: var(--gray);
            transition: var(--transition);
            border: none;
        }
        
        .action-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Activity List */
        .activity-list {
            padding: 0 28px 28px;
        }
        
        .activity-item {
            padding: 18px 0;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .activity-partner {
            font-weight: 600;
            color: var(--dark);
        }
        
        .activity-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .activity-order {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .activity-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .activity-time {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-icon {
            font-size: 3.5rem;
            color: var(--light-gray);
            margin-bottom: 20px;
        }
        
        .empty-text {
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            padding: 10px 24px;
            font-weight: 500;
            border-radius: 12px;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.4);
        }
        
        .btn-primary i {
            margin-right: 8px;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 1199px) {
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .dashboard-container {
                padding: 15px;
            }
            
            .card-header, .table-responsive, .activity-list {
                padding: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header h2 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2><i class="fas fa-motorcycle"></i> Delivery Partners Dashboard</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Delivery Partners</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('admin.delivery-partners.index') }}" class="btn btn-primary">
                    <i class="fas fa-list"></i> View All Partners
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards - ALL in one row -->
    <div class="stats-container">
        <div class="stat-card total-partners">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div class="stat-title">Total Partners</div>
                <div class="stat-value">{{ $stats['total_partners'] ?? 0 }}</div>
            </div>
        </div>

        <div class="stat-card online-now">
            <div class="stat-icon">
                <i class="fas fa-circle"></i>
            </div>
            <div>
                <div class="stat-title">Online Now</div>
                <div class="stat-value">{{ $stats['online_partners'] ?? 0 }}</div>
            </div>
        </div>

        <div class="stat-card available">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div class="stat-title">Available</div>
                <div class="stat-value">{{ $stats['available_partners'] ?? 0 }}</div>
            </div>
        </div>

        <div class="stat-card pending">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="stat-title">Pending Approval</div>
                <div class="stat-value">{{ $stats['pending_partners'] ?? 0 }}</div>
            </div>
        </div>

        <div class="stat-card active-deliveries">
            <div class="stat-icon">
                <i class="fas fa-motorcycle"></i>
            </div>
            <div>
                <div class="stat-title">Active Deliveries</div>
                <div class="stat-value">{{ $stats['active_deliveries'] ?? 0 }}</div>
            </div>
        </div>

        <div class="stat-card completed-today">
            <div class="stat-icon">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <div class="stat-title">Completed Today</div>
                <div class="stat-value">{{ $stats['completed_today'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Online Partners & Recent Activity -->
    <div class="row">
        <div class="col-lg-8">
            <div class="content-card">
                <div class="card-header">
                    <h5>Online Delivery Partners</h5>
                </div>
                <div class="card-body">
                    @if(isset($onlinePartners) && $onlinePartners->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Rating</th>
                                        <th>Last Active</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($onlinePartners as $partner)
                                        <tr>
                                            <td>
                                                <div class="partner-name">
                                                    <span class="status-indicator {{ $partner->is_available ? 'status-available' : 'status-busy' }}"></span>
                                                    {{ $partner->name }}
                                                </div>
                                            </td>
                                            <td>{{ $partner->phone }}</td>
                                            <td>
                                                @if($partner->is_available)
                                                    <span class="badge-status badge-available">Available</span>
                                                @else
                                                    <span class="badge-status badge-busy">Busy</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="rating">
                                                    <i class="fas fa-star"></i>
                                                    {{ number_format($partner->rating ?? 0, 1) }}
                                                </span>
                                            </td>
                                            <td>{{ $partner->updated_at?->diffForHumans() ?? 'N/A' }}</td>
                                            <td>
                                                <a href="{{ route('admin.delivery-partners.show', $partner->id) }}" class="action-btn">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-users empty-icon"></i>
                            <p class="empty-text">No delivery partners online at the moment</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="content-card">
                <div class="card-header">
                    <h5>Recent Activity</h5>
                </div>
                <div class="card-body">
                    @if(isset($recentActivity) && count($recentActivity) > 0)
                        <div class="activity-list">
                            @foreach($recentActivity as $activity)
                                <div class="activity-item">
                                    <div class="activity-header">
                                        <div class="activity-partner">{{ $activity->deliveryPartner->name ?? 'Unknown' }}</div>
                                        <div class="activity-time">{{ $activity->updated_at->diffForHumans() }}</div>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="activity-order">Order #{{ $activity->order->id ?? 'N/A' }}</span>
                                        <span class="activity-badge bg-{{ $activity->status === 'completed' ? 'success' : 'primary' }}">
                                            {{ ucfirst($activity->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-bell empty-icon"></i>
                            <p class="empty-text">No recent activity</p>
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