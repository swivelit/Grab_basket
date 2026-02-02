<?php

use Illuminate\Support\Facades\Route;

// Extremely simple test route
Route::get('/simple-test', function () {
    return 'Laravel is working! ' . date('Y-m-d H:i:s');
});

// Test route that shows basic info
Route::get('/info', function () {
    return response()->json([
        'status' => 'working',
        'timestamp' => now(),
        'php_version' => phpversion(),
        'laravel_version' => app()->version(),
        'environment' => app()->environment(),
    ]);
});

// Test if we can handle a basic view
Route::get('/view-test', function () {
    return view('welcome');
});

// Hotel Owner Wallet Routes
Route::middleware(['auth', 'hotel.owner'])->group(function () {
    // Wallet Overview
    Route::get('/hotel-owner/wallet', 'HotelOwnerWalletController@index')->name('hotel-owner.wallet.index');
    Route::get('/hotel-owner/wallet/earnings', 'HotelOwnerWalletController@earnings')->name('hotel-owner.wallet.earnings');
    
    // Withdrawals
    Route::get('/hotel-owner/withdrawals', 'HotelOwnerWalletController@withdrawals')->name('hotel-owner.withdrawals.index');
    Route::post('/hotel-owner/withdrawals', 'HotelOwnerWalletController@requestWithdrawal')->name('hotel-owner.withdrawals.request');
});

// Admin Wallet Management Routes
Route::middleware(['auth', 'admin'])->group(function () {
    // Wallet Administration
    Route::get('/admin/hotel-owner-wallets', 'Admin\HotelOwnerWalletController@index')->name('admin.hotel-owner-wallets.index');
    Route::get('/admin/hotel-owner-wallets/{wallet}', 'Admin\HotelOwnerWalletController@show')->name('admin.hotel-owner-wallets.show');

    // Withdrawal Management
    Route::get('/admin/withdrawals', 'Admin\HotelOwnerWithdrawalController@index')->name('admin.withdrawals.index');
    Route::get('/admin/withdrawals/{withdrawal}', 'Admin\HotelOwnerWithdrawalController@show')->name('admin.withdrawals.show');
    Route::post('/admin/withdrawals/{withdrawal}/approve', 'Admin\HotelOwnerWithdrawalController@approve')->name('admin.withdrawals.approve');
    Route::post('/admin/withdrawals/{withdrawal}/reject', 'Admin\HotelOwnerWithdrawalController@reject')->name('admin.withdrawals.reject');
});