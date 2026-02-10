<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DebugController;

Route::get('/health-check', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'laravel_version' => app()->version()
    ]);
});

Route::get('/debug-product/{id}', function ($id) {
    try {
        // Test database connection first
        $dbStatus = DB::connection()->getPdo() ? 'connected' : 'failed';
        
        return response()->json([
            'database_status' => $dbStatus,
            'product_id' => $id,
            'app_debug' => config('app.debug'),
            'environment' => config('app.env')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

Route::get('/test-product-data/{id}', [DebugController::class, 'testProduct']);
Route::get('/test-product-view/{id}', [DebugController::class, 'testView']);