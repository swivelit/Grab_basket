@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Bulk Image Upload Test</h2>
    
    <div class="alert alert-success">
        <strong>Success!</strong> The controller and view are working properly.
    </div>
    
    <div class="card">
        <div class="card-header">
            <h4>System Status</h4>
        </div>
        <div class="card-body">
            <p><strong>Categories Available:</strong> {{ count($categories) }}</p>
            <p><strong>ZipArchive Class:</strong> {{ class_exists('ZipArchive') ? 'Available' : 'Not Available' }}</p>
            <p><strong>PHP Version:</strong> {{ PHP_VERSION }}</p>
            <p><strong>Storage Driver:</strong> {{ config('filesystems.default') }}</p>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="{{ route('seller.dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
    </div>
</div>
@endsection