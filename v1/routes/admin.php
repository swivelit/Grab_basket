<?php

// Admin Delivery Partner Management Routes
Route::prefix('admin/delivery-partners')->middleware('web')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'index'])
        ->name('admin.delivery-partners.index');
    Route::get('/{deliveryPartner}', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'show'])
        ->name('admin.delivery-partners.show');
    Route::post('/{deliveryPartner}/status', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'updateStatus'])
        ->name('admin.delivery-partners.status');
    Route::get('/{deliveryPartner}/documents', [App\Http\Controllers\Admin\DeliveryPartnerController::class, 'viewDocuments'])
        ->name('admin.delivery-partners.documents');
});