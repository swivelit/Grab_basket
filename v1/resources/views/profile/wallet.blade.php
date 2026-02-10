<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Wallet - GrabBaskets</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #667eea, #764ba2);
  min-height: 100vh;
  padding: 15px;
}

.wallet-container {
  max-width: 600px;
  margin: auto;
}

.wallet-card {
  background: #fff;
  border-radius: 22px;
  padding: 25px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.25);
}

.wallet-header {
  text-align: center;
  margin-bottom: 25px;
}

.wallet-icon {
  width: 90px;
  height: 90px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: auto;
  box-shadow: 0 8px 25px rgba(102,126,234,.4);
}

.wallet-icon i {
  font-size: 3rem;
  color: white;
}

.wallet-title {
  font-weight: 800;
  margin-top: 15px;
}

.role-badge {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  padding: 5px 14px;
  border-radius: 20px;
  font-size: .75rem;
}

.balance-section {
  background: #f8f9fa;
  border-radius: 16px;
  padding: 20px;
  text-align: center;
  margin-bottom: 25px;
  border: 2px solid #e0e0e0;
}

.balance-amount {
  font-size: 3rem;
  font-weight: 900;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.balance-points {
  font-weight: 600;
  color: #666;
}

.info-card {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 15px;
  border-left: 4px solid #667eea;
}

.action-buttons {
  display: flex;
  gap: 12px;
}

.btn-wallet {
  flex: 1;
  padding: 12px;
  font-weight: 700;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-primary-wallet {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
}

.btn-secondary-wallet {
  border: 2px solid #667eea;
  color: #667eea;
  background: white;
}

.btn-secondary-wallet:hover {
  background: #667eea;
  color: white;
}

/* 📱 MOBILE OPTIMIZATION */
@media (max-width: 576px) {
  .wallet-card {
    padding: 20px;
  }

  .wallet-icon {
    width: 75px;
    height: 75px;
  }

  .balance-amount {
    font-size: 2.5rem;
  }

  .action-buttons {
    flex-direction: column;
  }
}
</style>
</head>

<body>

<div class="wallet-container">

<a href="{{ route('profile.show') }}" class="text-white fw-bold mb-3 d-inline-block">
  <i class="bi bi-arrow-left"></i> Back to Profile
</a>

<div class="wallet-card">

  {{-- HEADER --}}
  <div class="wallet-header">
    <div class="wallet-icon">
      @if(auth()->user()->profile_picture)
        <img src="{{ asset('storage/' . auth()->user()->profile_picture) }}"
             style="width:70px;height:70px;border-radius:50%;object-fit:cover;">
      @else
        <i class="bi bi-person-circle"></i>
      @endif
    </div>

    <h4 class="wallet-title">Hello, {{ auth()->user()->name }} 👋</h4>
    <span class="role-badge">{{ ucfirst(auth()->user()->role) }} Account</span>
  </div>

  {{-- BUYER CHECK --}}
  @if(auth()->user()->role === 'buyer')

  {{-- BALANCE --}}
  <div class="balance-section">
    <div class="text-uppercase text-muted small fw-bold">Wallet Balance</div>
    <div class="balance-amount">₹{{ number_format($walletAmount,2) }}</div>
    <div class="balance-points">{{ $walletPoints }} Points</div>
  </div>

  {{-- INFO --}}
  <div class="info-card">
    <strong>💡 Wallet Rule</strong><br>
    1 Point = ₹1. Use 150 points on orders above ₹2000.
  </div>

  <div class="info-card">
    <strong>🎁 Earn Rewards</strong><br>
    Get 20 points after every order above ₹2000.
  </div>

  <div class="info-card">
    <strong>🏷 Discount</strong><br>
    Save ₹150 using wallet points on eligible orders.
  </div>

  {{-- ACTIONS --}}
  <div class="action-buttons mt-3">
    <a href="/" class="btn-wallet btn-primary-wallet">
      <i class="bi bi-cart-fill"></i> Shop Now
    </a>
    <a href="{{ route('orders.track') }}" class="btn-wallet btn-secondary-wallet">
      <i class="bi bi-box-seam"></i> Orders
    </a>
  </div>

  @else
    <div class="alert alert-warning text-center">
      Wallet is available only for buyers
    </div>
  @endif

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
