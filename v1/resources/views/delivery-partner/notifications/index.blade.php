@extends('delivery-partner.layouts.dashboard')

@section('title', 'Notifications')

@section('content')
<div class="container py-4">
    <h2>Notifications</h2>
    <p class="text-muted">Stay updated with your delivery updates</p>
    
    @if(count($notifications) > 0)
        <div class="list-group">
            @foreach($notifications as $notification)
                <!-- Notification items will go here -->
            @endforeach
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No notifications yet.
        </div>
    @endif
</div>
@endsection
