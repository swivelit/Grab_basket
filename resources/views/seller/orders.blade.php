<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seller Orders</title>
  <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbasket.jpg') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      width: 240px;
      background: #212529;
      color: #fff;
      padding-top: 60px;
      transition: all 0.3s;
      z-index: 1000;
    }

    .sidebar .nav-link {
      color: #adb5bd;
      margin: 6px 0;
      border-radius: 6px;
    }

    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background: #0d6efd;
      color: #fff;
    }

    .sidebar .nav-link i {
      margin-right: 8px;
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
    
  </style>
</head>

<body>
  <!-- Toggle Button (mobile only) -->
  <div class="menu-toggle d-md-none">
    <i class="bi bi-list"></i>
  </div>

  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column p-3" id="sidebarMenu">
    <img src="{{ asset('asset/images/grabbasket.png') }}" alt="Logo"  width="180px">
    <!-- <h4 class="text-white mb-4">🛒GrabBasket</h4> -->
    <ul class="nav nav-pills flex-column" style="margin-top:65px;">
      <li>
        <a class="nav-link" href="{{ route('seller.createProduct') }}">
          <i class="bi bi-plus-circle"></i> Add Product
        </a>
      </li>
      <li>
        <a class="nav-link" href="{{ route('seller.createCategorySubcategory') }}">
          <i class="bi bi-plus-square"></i> Add Category
        </a>
      </li>
      <li>
        <a class="nav-link" href="{{ route('seller.dashboard') }}">
          <i class="bi bi-speedometer2"></i> Dashboard
        </a>
      </li>
      <li>
        <a class="nav-link active" href="{{ route('seller.transactions') }}">
          <i class="bi bi-cart-check"></i> Orders
        </a>
      </li>
      <li>
        <a class="nav-link" href="{{ route('tracking.form') }}">
          <i class="bi bi-truck"></i> Track Package
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

  <!-- Content -->
  <div class="content">
    <div class="container-fluid mt-4">
      <h2 class="mb-4"><i class="bi bi-cart-check"></i> Orders</h2>

      @if(isset($orders) && $orders->count())
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Product</th>
              <th>Quantity</th>
              <th>Total</th>
              <th>Status</th>
              <th>Tracking</th>
              <th>Ordered At</th>
            </tr>
          </thead>
          <tbody>
            @foreach($orders as $order)
            <tr>
              <td>{{ $order->product_id }}</td>
              <td>{{ $order->customer->name ?? 'Unknown' }}</td>
              <td>{{ $order->product->name ?? '-' }}</td>
              <td>{{ $order->quantity }}</td>
              <td>₹{{ number_format($order->amount, 2) }}</td>
              <td>
                @if($order->status === 'Delivered')
                <span class="badge bg-success">Delivered</span>
                @elseif($order->status === 'Pending')
                <span class="badge bg-warning">Pending</span>
                @elseif($order->status === 'Cancelled')
                <span class="badge bg-danger">Cancelled</span>
                @else
                <span class="badge bg-secondary">{{ $order->status }}</span>
                @endif
              </td>
              <td>
                <!-- ✅ Tracking number update for sellers -->
                @if($order->status !== 'Cancelled' && $order->status !== 'Delivered')
                <form action="{{ route('orders.updateTracking', $order->id) }}" method="POST" class="d-flex flex-column gap-1">
                  @csrf
                  <div class="d-flex gap-1">
                    <input type="text" name="tracking_number" value="{{ $order->tracking_number }}" class="form-control form-control-sm" placeholder="Tracking #" style="width: 120px;">
                    <select name="courier_name" class="form-select form-select-sm" style="width: 100px;">
                      <option value="">Courier</option>
                      <option value="Delhivery" {{ $order->courier_name == 'Delhivery' ? 'selected' : '' }}>Delhivery</option>
                      <option value="Blue Dart" {{ $order->courier_name == 'Blue Dart' ? 'selected' : '' }}>Blue Dart</option>
                      <option value="DTDC" {{ $order->courier_name == 'DTDC' ? 'selected' : '' }}>DTDC</option>
                      <option value="India Post" {{ $order->courier_name == 'India Post' ? 'selected' : '' }}>India Post</option>
                      <option value="FedEx" {{ $order->courier_name == 'FedEx' ? 'selected' : '' }}>FedEx</option>
                      <option value="Ecom Express" {{ $order->courier_name == 'Ecom Express' ? 'selected' : '' }}>Ecom Express</option>
                    </select>
                  </div>
                  <button type="submit" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-truck"></i> Update
                  </button>
                </form>
                @else
                  @if($order->tracking_number)
                    <small class="text-muted">
                      <strong>{{ $order->courier_name ?? 'Unknown Courier' }}</strong><br>
                      {{ $order->tracking_number }}
                      <a href="{{ route('tracking.form') }}?tracking_number={{ $order->tracking_number }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                        <i class="bi bi-search"></i> Track
                      </a>
                    </small>
                  @else
                    <small class="text-muted">No tracking info</small>
                  @endif
                @endif
              </td>
              <td>{{ $order->created_at?->format('d M Y, h:i A') }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <p class="text-muted">No orders available yet.</p>
      @endif
    </div>
  </div>

  <!-- JS for toggle -->
  <script>
    const toggleBtn = document.querySelector('.menu-toggle');
    const sidebar = document.getElementById('sidebarMenu');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('show');
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>