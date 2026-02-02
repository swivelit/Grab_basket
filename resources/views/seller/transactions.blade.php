<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seller Orders</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background-color: #f8f9fa;
    }

    /* === SIDEBAR === */
    .sidebar {
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      width: 260px;
      background: #1a1a1a;
      color: #fff;
      transition: all 0.3s ease;
      z-index: 1000;
      height: 100vh;
      overflow-y: auto;
      /* ✅ Scroll inside sidebar */
      overflow-x: hidden;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
    }

    /* === SIDEBAR LOGO BOX === */
    /* === SIDEBAR LOGO BOX === */
    .sidebar-logo-box {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 4px 10px;
      background: #003366;
      border-radius: 6px;
      width: 100%;
      box-sizing: border-box;
    }

    .sidebar-logo-img {
      width: 150px;
      height: 200px;
      object-fit: cover;
      margin-top: -3px;
      /* Pull up slightly to counter height increase */
      margin-bottom: -3px;
    }

    .sidebar-logo-text {
      color: #fff;
      font-size: 0.85rem;
      line-height: 1.1;
      text-align: left;
      margin: 0;
      padding: 0;
    }

    .sidebar-logo-text strong {
      font-size: 0.95rem;
      display: block;
      font-weight: 600;
    }

    .sidebar-logo-text small {
      opacity: 0.8;
      font-size: 0.7rem;
      font-weight: 400;
    }

    /* Fixed Header */
    .sidebar-header {
      position: sticky;
      top: 0;
      padding: 12px 20px;
      z-index: 1001;
      /* Must be higher than other sidebar content */
      background: #1a1a1a;
      /* Match sidebar background to avoid "ghosting" */
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 100px;
      /* Adjusted height for better fit */
    }

    .sidebar-header .logoimg {
      width: 130px;
      height: auto;
      filter: brightness(0.9);
    }

    .sidebar-header .notification-bell {
      font-size: 1.2rem;
      color: #adb5bd;
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.05);
      transition: background 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 32px;
      width: 32px;
    }

    .sidebar-header .notification-bell:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
    }

    /* Scrollable Content */
    .sidebar-content {
      padding: 0;
      padding-bottom: 60px;
      /* Prevent logout from sticking to bottom */
      margin-top: 30px;
    }

    /* === NOTIFICATION BELL DROPDOWN FIX === */
    .sidebar-header .notification-bell {
      position: relative;
      /* Ensure it's a positioning context for its children */
    }

    /* Target the notification dropdown (assuming it's a direct child or descendant of the bell) */
    .sidebar-header .notification-bell~.dropdown-menu,
    .sidebar-header .notification-bell+.dropdown-menu,
    .sidebar-header .notification-bell .dropdown-menu {
      position: absolute;
      top: 100%;
      /* Position below the bell */
      left: 50%;
      /* Start from the center of the bell */
      transform: translateX(-50%);
      /* Center it horizontally */
      z-index: 1002;
      /* Higher than the sidebar (z-index: 1000) */
      background: #fff;
      border: 1px solid rgba(0, 0, 0, 0.15);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      border-radius: 8px;
      min-width: 280px;
      max-width: 320px;
      padding: 1rem;
      margin-top: 0.5rem;
    }

    /* Optional: If the dropdown needs to appear on the right side specifically */
    .sidebar-header .notification-bell .dropdown-menu {
      left: auto;
      /* Override the centering */
      right: -10px;
      /* Position slightly to the right of the bell */
      transform: none;
      /* Remove centering transform */
    }

    /* Ensure the dropdown doesn't get clipped by the sidebar */
    .sidebar-header .notification-bell .dropdown-menu {
      /* This is the key: use 'fixed' positioning to escape the sidebar's bounds */
      position: fixed;
      top: calc(100% + 0px);
      /* Position below the header with a small gap */
      left: calc(100vw - 350px);
      /* Position near the right edge of the viewport */
      width: 320px;
      z-index: 1002;
      background: #fff;
      border: 1px solid rgba(0, 0, 0, 0.15);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      border-radius: 8px;
      padding: 1rem;
    }

    /* === NOTIFICATION BELL DROPDOWN FIX === */
    /* === NOTIFICATION BELL DROPDOWN FIX === */
    .dropdown-menu {
      position: fixed !important;
      top: 0 !important;
      /* Fixed at the very top of the screen */
      right: 20px !important;
      /* Position near the right edge */
      z-index: 1002 !important;
      /* Ensure it's above the sidebar */
      width: 320px !important;
      background: #fff !important;
      border: 1px solid rgba(0, 0, 0, 0.15) !important;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
      border-radius: 8px !important;
      padding: 1rem !important;
    }

    /* Optional: Adjust the arrow if needed */
    .dropdown-menu::before {
      display: none !important;
    }

    .sidebar-content::-webkit-scrollbar {
      width: 6px;
    }

    .sidebar-content::-webkit-scrollbar-track {
      background: #2d2d2d;
      border-radius: 10px;
    }

    .sidebar-content::-webkit-scrollbar-thumb {
      background: #555;
      border-radius: 10px;
    }

    .sidebar-content::-webkit-scrollbar-thumb:hover {
      background: #777;
    }

    /* Nav Links */
    .sidebar .nav-link {
      color: #adb5bd;
      margin: 6px 15px;
      border-radius: 6px;
      padding: 10px 15px;
      transition: all 0.2s ease;
    }

    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
      background: linear-gradient(90deg, #0d6efd, #6610f2);
      color: white;
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
    }

    .sidebar .nav-link i {
      margin-right: 10px;
      font-size: 1.1rem;
    }

    /* Logout Highlight */
    .sidebar .nav-link[href="#"] {
      color: #dc3545;
    }

    .sidebar .nav-link[href="#"]:hover {
      background: #dc3545;
      color: white;
    }

    /* === CONTENT AREA === */
    .content {
      margin-left: 240px;
      padding: 20px;
      transition: margin-left 0.3s ease;
      min-height: 100vh;
      /* Ensure full height */
      background: #f8f9fa;
      position: relative;
      z-index: 999;
      /* Ensure content stays above other elements */
    }

    /* === MOBILE TOGGLE === */
    .menu-toggle {
      position: fixed;
      top: 15px;
      left: 15px;
      font-size: 1.8rem;
      cursor: pointer;
      color: #fff;
      z-index: 1101;
      background: #212529;
      padding: 8px;
      border-radius: 50%;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .menu-toggle:hover {
      background: #343a40;
    }

    @media (max-width: 768px) {
      .sidebar {
        left: -240px;
        height: 100vh;
        overflow-y: auto;
        z-index: 1001;
        /* Higher than content */
      }

      .sidebar.show {
        left: 0;
      }


      .menu-toggle {
        color: #fff;
        background: #212529;
      }
    }

    /* Content */
    .content {
      margin-left: 240px;
      padding: 20px;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar {
        left: -240px;
      }

      .sidebar.show {
        left: 0;
      }

      .content {
        margin-left: 0;
      }
    }

    /* Toggle button */
    .menu-toggle {
      position: fixed;
      top: 15px;
      left: 15px;
      font-size: 1.8rem;
      cursor: pointer;
      color: #212529;
      z-index: 1101;
    }

    .table img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 8px;
    }

    .badge {
      font-size: 0.9rem;
    }
  </style>
</head>

<body>
  <!-- Toggle Button (mobile only) -->
  <div class="menu-toggle d-md-none">
    <i class="bi bi-list"></i>
  </div>

  <!-- Sidebar -->
  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column p-0" id="sidebarMenu">
    <div class="sidebar-header">
      <img src="{{ asset('asset/images/grabbasket.png') }}" alt="Logo" class="sidebar-logo-img">
      <x-notification-bell />
    </div>

    <div class="sidebar-content">
      <ul class="nav nav-pills flex-column" style="margin-top: 20px;">
        <li>
          <a class="nav-link" href="{{ route('seller.createProduct') }}">
            <i class="bi bi-plus-circle"></i> Add Product
          </a>
        </li>
        <li>
          <a class="nav-link" href="{{ route('seller.imageLibrary') }}">
            <i class="bi bi-images"></i> Image Library
          </a>
        </li>
        <li>
          <a class="nav-link" href="{{ route('seller.bulkUploadForm') }}">
            <i class="bi bi-cloud-upload"></i> Bulk Upload Excel
          </a>
        </li>
        <li>
          <a class="nav-link" href="{{ route('seller.bulkImageReupload') }}">
            <i class="bi bi-images"></i> Bulk Image Re-upload
          </a>
        </li>
        <li>
          <a class="nav-link" href="{{ route('seller.createCategorySubcategory') }}">
            <i class="bi bi-plus-square"></i> Add Category
          </a>
        </li>
        <li>
          <a class="nav-link " href="{{ route('seller.dashboard') }}">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
        </li>
        <li>
          <a class="nav-link active" href="{{ route('seller.transactions') }}">
            <i class="bi bi-cart-check"></i> Orders
          </a>
        </li>
        <li>
          <a class="nav-link" href="{{ route('seller.importExport') }}">
            <i class="bi bi-arrow-down-up"></i> Import / Export
          </a>
        </li>
        <li>
          <a class="nav-link" href="{{ route('tracking.form') }}">
            <i class="bi bi-truck"></i> Track Package
          </a>
        </li>
        <li>
          <a class="nav-link" href="{{ route('notifications.index') }}">
            <i class="bi bi-bell"></i> Notifications
          </a>
        </li>
        <li>
          <a class="nav-link" href="{{ route('seller.profile') }}">
            <i class="bi bi-person-circle"></i> Profile
          </a>
        </li>
        <li>
          <a class="nav-link" href="#"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </li>
      </ul>
      <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
      </form>
    </div>
  </div>

  <!-- Main Content -->
  <div class="content">
    <div class="container-fluid mt-4">
      <h2 class="mb-4"><i class="bi bi-cart-check"></i> Seller Orders</h2>

      @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      @if($orders->isEmpty())
      <div class="alert alert-info text-center py-5">
        <i class="bi bi-box" style="font-size: 4rem; color: #ccc;"></i>
        <h4 class="mt-3 text-muted">No orders found</h4>
        <p>Orders for your products will appear here soon.</p>
      </div>
      @else
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
              <thead class="table-dark">
                <tr>
                  <th>Image</th>
                  <th>Product</th>
                  <th>Buyer</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th>Payment Method</th>
                  <th>Tracking #</th>
                  <th>Placed At</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($orders as $order)
                <tr>
                  <td>
                    @if($order->product && ($order->product->image || $order->product->image_data))
                    <img src="{{ $order->product->image_url }}" alt="{{ $order->product->name }}">
                    @else
                    <img src="{{ asset('images/no-image.png') }}" alt="No Image">
                    @endif
                  </td>
                  <td>{{ $order->product->name ?? '-' }}</td>
                  <td>{{ $order->buyerUser->name ?? 'Unknown Buyer' }}</td>
                  <td>₹{{ number_format($order->amount, 2) }}</td>
                  <td>
                    <span class="badge 
                      @if($order->status === 'delivered') bg-success 
                      @elseif($order->status === 'pending') bg-warning text-dark 
                      @elseif($order->status === 'cancelled') bg-danger 
                      @else bg-secondary @endif">
                      {{ ucfirst($order->status) }}
                    </span>
                  </td>
                  <td>{{ ucfirst($order->payment_method) ?? 'N/A' }}</td>
                  <td>
                    @if(in_array($order->status, ['shipped', 'confirmed']))
                    <form action="{{ route('orders.updateTracking', $order->id) }}" method="POST" class="d-flex align-items-center">
                      @csrf
                      <input type="text" name="tracking_number" value="{{ $order->tracking_number }}" class="form-control form-control-sm me-2" placeholder="Enter tracking #" required>
                      <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </form>
                    @else
                    {{ $order->tracking_number ?? '-' }}
                    @endif
                  </td>
                  <td>{{ $order->created_at->format('d M Y, h:i A') }}</td>
                  <td>
                    <form method="POST" action="{{ route('orders.updateStatus', $order) }}" class="d-flex gap-2">
                      @csrf
                      <select name="status" class="form-select form-select-sm">
                        <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                      </select>
                      <button type="submit" class="btn btn-outline-primary btn-sm">Update</button>
                    </form>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-center mt-3">
            {{ $orders->links() }}
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>

  <script>
    const toggleBtn = document.querySelector('.menu-toggle');
    const sidebar = document.getElementById('sidebarMenu');
    toggleBtn.addEventListener('click', () => sidebar.classList.toggle('show'));
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>