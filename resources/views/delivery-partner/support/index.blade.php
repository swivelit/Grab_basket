@extends('delivery-partner.layouts.dashboard')

@section('title', 'Support & Help')

@section('content')
<div class="container py-4">
    <h2>Support & Help</h2>
    <p class="text-muted">Get help with your delivery partner account</p>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Submit a Support Ticket</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('delivery-partner.support.submit') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-control" required>
                                <option value="">Select category</option>
                                <option value="account">Account Issues</option>
                                <option value="payment">Payment Issues</option>
                                <option value="order">Order Issues</option>
                                <option value="technical">Technical Issues</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Ticket</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Contact Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Email:</strong> support@grabbaskets.com</p>
                    <p><strong>Phone:</strong> +91 1234567890</p>
                    <p><strong>Hours:</strong> 24/7</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
