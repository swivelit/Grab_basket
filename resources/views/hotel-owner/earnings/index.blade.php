@extends('layouts.minimal')

@section('title', 'Earnings')

@section('content')
<div class="container py-4">

    <!-- Back Button -->
    <a href="{{ route('hotel-owner.dashboard') }}" class="btn btn-secondary mb-3">
        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
    </a>

    <div class="row">
        <!-- Earnings Chart -->
        <div class="col-md-8 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0">Earnings</h5>
                    <div class="d-flex gap-2 mt-2 mt-md-0 ms-3">
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-range="7">Last 7 Days</button>
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-range="30">Last 30 Days</button>
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-range="month">Last 12 Months</button>
                    </div>
                    <small class="text-muted ms-auto mt-2 mt-md-0">Total: ₹{{ number_format($earnings['total'] ?? 0, 2) }}</small>
                </div>
                <div class="card-body">
                    <canvas id="earningsChart" height="150"></canvas>
                </div>
            </div>
        </div>

        <!-- Earnings Summary -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6>Summary</h6>
                    <p>Today: <strong>₹{{ number_format($earnings['today'] ?? 0, 2) }}</strong></p>
                    <p>This week: <strong>₹{{ number_format($earnings['week'] ?? 0, 2) }}</strong></p>
                    <p>This month: <strong>₹{{ number_format($earnings['month'] ?? 0, 2) }}</strong></p>
                </div>
            </div>
        </div>

        <!-- Withdraw Section -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6>Withdraw</h6>
                    @php
                        $wallet = \App\Models\HotelOwnerWallet::firstOrCreate(
                            ['hotel_owner_id' => ($hotelOwner->id ?? null)],
                            ['balance' => 0, 'currency' => 'INR']
                        );
                    @endphp
                    <p>Available balance: <strong>₹{{ number_format($wallet->balance, 2) }}</strong></p>
                    <form method="POST" action="{{ route('hotel-owner.wallet.withdraw') }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-4">
                                <input type="number" name="amount" step="0.01" min="1" class="form-control" placeholder="Amount">
                            </div>
                            <div class="col-6">
                                <input type="text" name="notes" class="form-control" placeholder="Notes (optional)">
                            </div>
                            <div class="col-2">
                                <button class="btn btn-danger w-100">Request</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Internal CSS -->
<style>
    .filter-btn.active {
        background-color: #E23744;
        color: #fff;
        border-color: #E23744;
    }
</style>

<!-- Internal JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('earningsChart').getContext('2d');
    let chart;

    function fetchEarnings(range = '7') {
        fetch("{{ route('hotel-owner.earnings.fetch') }}?range=" + range)
            .then(res => res.json())
            .then(res => {
                if(chart) chart.destroy();
                chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: res.labels,
                        datasets: [{
                            label: 'Earnings',
                            data: res.data,
                            backgroundColor: 'rgba(226,55,68,0.08)',
                            borderColor: '#E23744',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            });
    }

    fetchEarnings('7');

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            fetchEarnings(this.dataset.range);
        });
    });
});
</script>
@endsection
