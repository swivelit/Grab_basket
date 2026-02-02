<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile | GrabBaskets</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    :root {
      --primary: #ff8c00;
      --primary-light: #fff5e6;
      --secondary: #2c3e50;
      --text-main: #1a1a1a;
      --text-muted: #666;
      --bg-body: #f8f9fb;
      --white: #ffffff;
      --shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Outfit', sans-serif;
      background: var(--bg-body);
      color: var(--text-main);
      line-height: 1.5;
      padding-bottom: 50px;
    }

    .header {
      background: var(--secondary);
      padding: 40px 20px 80px;
      color: var(--white);
      text-align: center;
      position: relative;
    }

    .back-btn {
      position: absolute;
      left: 20px;
      top: 20px;
      color: var(--white);
      text-decoration: none;
      font-size: 1.2rem;
    }

    .container {
      max-width: 600px;
      margin: -60px auto 0;
      padding: 0 20px;
    }

    /* Profile Card */
    .profile-main-card {
      background: var(--white);
      border-radius: 24px;
      padding: 30px;
      box-shadow: var(--shadow);
      text-align: center;
      margin-bottom: 24px;
    }

    .avatar-wrapper {
      position: relative;
      width: 100px;
      height: 100px;
      margin: 0 auto 15px;
    }

    .avatar-wrapper img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid var(--primary-light);
    }

    .edit-avatar-btn {
      position: absolute;
      bottom: 0;
      right: 0;
      background: var(--primary);
      color: var(--white);
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid var(--white);
      cursor: pointer;
    }

    .user-name {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .user-phone {
      color: var(--text-muted);
      font-size: 0.95rem;
      margin-bottom: 20px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      margin-top: 20px;
      border-top: 1px solid #f0f0f0;
      padding-top: 20px;
    }

    .stat-item {
      text-align: center;
    }

    .stat-value {
      display: block;
      font-weight: 700;
      color: var(--primary);
      font-size: 1.1rem;
    }

    .stat-label {
      font-size: 0.8rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Quick Actions */
    .section-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin: 30px 0 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .quick-actions-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .action-card {
      background: var(--white);
      border-radius: 20px;
      padding: 20px;
      text-decoration: none;
      color: var(--text-main);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      box-shadow: var(--shadow);
      transition: transform 0.2s, box-shadow 0.2s;
      border: 1px solid transparent;
    }

    .action-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
      border-color: var(--primary-light);
    }

    .action-icon {
      width: 50px;
      height: 50px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 5px;
    }

    .icon-wallet {
      background: #fff5e6;
      color: #ff8c00;
    }

    .icon-ref {
      background: #e6f7ff;
      color: #1890ff;
    }

    .icon-orders {
      background: #f6ffed;
      color: #52c41a;
    }

    .icon-wish {
      background: #fff1f0;
      color: #ff4d4f;
    }

    .action-label {
      font-weight: 600;
      font-size: 0.95rem;
    }

    /* Info Cards */
    .info-card {
      background: var(--white);
      border-radius: 20px;
      padding: 20px;
      box-shadow: var(--shadow);
      margin-bottom: 15px;
    }

    .info-item {
      display: flex;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #f8f9fb;
    }

    .info-item:last-child {
      border-bottom: none;
    }

    .info-icon {
      width: 36px;
      color: var(--text-muted);
      font-size: 1.2rem;
    }

    .info-content {
      flex: 1;
    }

    .info-label {
      font-size: 0.8rem;
      color: var(--text-muted);
      display: block;
    }

    .info-value {
      font-weight: 500;
      font-size: 0.95rem;
    }

    /* Buttons */
    .btn-container {
      margin-top: 30px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .btn-primary {
      background: var(--primary);
      color: var(--white);
      text-decoration: none;
      padding: 16px;
      border-radius: 16px;
      font-weight: 600;
      text-align: center;
      transition: opacity 0.2s;
    }

    .btn-outline {
      background: transparent;
      color: var(--secondary);
      border: 1.5px solid var(--secondary);
      text-decoration: none;
      padding: 14px;
      border-radius: 16px;
      font-weight: 600;
      text-align: center;
    }

    .alert {
      margin-bottom: 20px;
      padding: 15px;
      border-radius: 12px;
      font-size: 0.9rem;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
    }

    .alert-danger {
      background: #f8d7da;
      color: #721c24;
    }

    @media (max-width: 480px) {
      .header {
        padding: 30px 15px 70px;
      }

      .container {
        padding: 0 15px;
      }

      .profile-main-card {
        padding: 20px;
      }
    }
  </style>
</head>

<body>

  <div class="header">
    <a href="{{ route('home') }}" class="back-btn"><i class="bi bi-arrow-left"></i></a>
    <h2 style="font-weight: 600; letter-spacing: -0.5px;">Account</h2>
  </div>

  <div class="container">
    <!-- Messages -->
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <ul style="list-style: none;">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- Main Profile Card -->
    <div class="profile-main-card">
      <div class="avatar-wrapper">
        <img
          src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=fff5e6&color=ff8c00&size=200&bold=true"
          alt="User Avatar">
        <div class="edit-avatar-btn"><i class="bi bi-camera"></i></div>
      </div>
      <h3 class="user-name">{{ $user->name }}</h3>
      <p class="user-phone">{{ $user->phone ?? 'Add phone number' }}</p>

      <div class="stats-grid">
        <div class="stat-item">
          <span class="stat-value">₹{{ number_format($user->wallet_point ?? 0, 0) }}</span>
          <span class="stat-label">Balance</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">{{ number_format($user->wallet_point ?? 0, 0) }}</span>
          <span class="stat-label">Points</span>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <h4 class="section-title">Quick Actions</h4>
    <div class="quick-actions-grid">
      <a href="{{ route('wallet.show') }}" class="action-card">
        <div class="action-icon icon-wallet"><i class="bi bi-wallet2"></i></div>
        <span class="action-label">My Wallet</span>
      </a>
      <a href="{{ route('buyer.referral') }}" class="action-card">
        <div class="action-icon icon-ref"><i class="bi bi-gift"></i></div>
        <span class="action-label">Refer & Earn</span>
      </a>
      <a href="/orders/track" class="action-card">
        <div class="action-icon icon-orders"><i class="bi bi-bag-check"></i></div>
        <span class="action-label">My Orders</span>
      </a>
      <a href="{{ route('wishlist.index') }}" class="action-card">
        <div class="action-icon icon-wish"><i class="bi bi-heart"></i></div>
        <span class="action-label">Wishlist</span>
      </a>
    </div>

    <!-- Personal Info -->
    <h4 class="section-title">Personal Details</h4>
    <div class="info-card">
      <div class="info-item">
        <div class="info-icon"><i class="bi bi-envelope"></i></div>
        <div class="info-content">
          <span class="info-label">Email Address</span>
          <span class="info-value">{{ $user->email }}</span>
        </div>
      </div>
      <div class="info-item">
        <div class="info-icon"><i class="bi bi-person"></i></div>
        <div class="info-content">
          <span class="info-label">Gender</span>
          <span class="info-value">{{ $user->sex ?? 'Not set' }}</span>
        </div>
      </div>
      <div class="info-item">
        <div class="info-icon"><i class="bi bi-calendar-event"></i></div>
        <div class="info-content">
          <span class="info-label">Date of Birth</span>
          <span class="info-value">{{ $user->dob ?? 'Not set' }}</span>
        </div>
      </div>
    </div>

    <!-- Address Info -->
    <h4 class="section-title">Saved Addresses</h4>
    <div class="info-card">
      <div class="info-item">
        <div class="info-icon"><i class="bi bi-geo-alt"></i></div>
        <div class="info-content">
          <span class="info-label">Default Delivery</span>
          <span class="info-value">{{ $user->default_address ?? 'No address saved yet' }}</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="btn-container">
      <a href="{{ route('profile.edit') }}" class="btn-primary">Edit Profile Details</a>
      <a href="{{ route('password.request') }}" class="btn-outline">Change Password</a>
    </div>

    <!-- Logout -->
    <div style="margin-top: 40px; text-align: center;">
      <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit"
          style="background: none; border: none; color: #ff4d4f; font-weight: 600; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;">
          <i class="bi bi-box-arrow-right"></i> Logout Account
        </button>
      </form>
    </div>
  </div>

</body>

</html>