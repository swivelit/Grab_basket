<?php

Route::prefix('api/delivery-partner')->middleware('auth:delivery_partner')->group(function () {
    Route::get('/dashboard/stats', [App\Http\Controllers\DeliveryPartner\DashboardController::class, 'getStats']);
    Route::get('/dashboard/orders', [App\Http\Controllers\DeliveryPartner\DashboardController::class, 'getOrders']);
    Route::get('/dashboard/notifications', [App\Http\Controllers\DeliveryPartner\DashboardController::class, 'getNotifications']);
});