@extends('layouts.minimal')

@section('title', 'Wallet')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Wallet Balance</h5>
                    <h3>₹{{ number_format($wallet->balance, 2) }}</h3>
                    <p class="text-muted">Currency: {{ $wallet->currency }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Request Withdrawal</h5>
                    <form method="POST" action="{{ route('hotel-owner.wallet.withdraw') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (optional)</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button class="btn btn-danger">Request Withdrawal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
