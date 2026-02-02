@extends('delivery-partner.layouts.dashboard')

@section('title', 'Earnings')

@section('content')
<div class="container py-4">
    <h2>My Earnings</h2>
    <p class="text-muted">Track your earnings and withdrawal history</p>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Today</h6>
                    <h3>₹{{ number_format($earnings['today'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">This Week</h6>
                    <h3>₹{{ number_format($earnings['week'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">This Month</h6>
                    <h3>₹{{ number_format($earnings['month'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Earnings</h6>
                    <h3>₹{{ number_format($earnings['total'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Full earnings tracking coming soon!
    </div>
</div>
@endsection
