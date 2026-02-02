<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CourierTrackingService;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class CourierTrackingController extends Controller
{
    /**
     * Show the tracking form
     */
    public function showForm()
    {
        $supportedCouriers = [
            'delhivery' => 'Delhivery',
            'bluedart' => 'Blue Dart',
            'dtdc' => 'DTDC',
            'indiapost' => 'India Post',
            'fedex' => 'FedEx',
            'ecom' => 'Ecom Express',
            'professional' => 'Professional Couriers',
            'gati' => 'GATI'
        ];

        $sampleTrackingNumbers = [
            'delhivery' => '1234567890123',
            'bluedart' => 'BD123456789',
            'dtdc' => 'D123456789',
            'indiapost' => 'RR123456789IN',
            'fedex' => '123456789012',
            'ecom' => 'E123456789',
            'professional' => 'PC123456789',
            'gati' => 'G123456789'
        ];

        return view('tracking.form', compact('supportedCouriers', 'sampleTrackingNumbers'));
    }

    /**
     * Track a package by tracking number
     */
    public function track(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:50',
            'courier' => 'nullable|string'
        ]);

        $trackingNumber = $request->tracking_number;
        $courier = $request->courier;

        $trackingData = CourierTrackingService::trackPackage($trackingNumber, $courier);

        if ($request->ajax()) {
            return response()->json($trackingData);
        }

        return view('tracking.result', compact('trackingData'));
    }

    /**
     * Show tracking form
     */
    public function showTrackingForm()
    {
        $supportedCouriers = CourierTrackingService::getSupportedCouriers();
        return view('tracking.form', compact('supportedCouriers'));
    }

    /**
     * Track order by order ID (for logged-in users)
     */
    public function trackOrder(Request $request, $orderId)
    {
        $order = Order::with(['product', 'sellerUser'])
                     ->where('id', $orderId)
                     ->where('buyer_id', Auth::id())
                     ->firstOrFail();

        if (!$order->tracking_number) {
            return back()->with('error', 'Tracking number not available for this order.');
        }

        $trackingData = CourierTrackingService::trackPackage($order->tracking_number);
        
        // Update order with latest tracking info
        $order->update([
            'tracking_status' => $trackingData['status'],
            'current_location' => $trackingData['current_location']
        ]);

        return view('tracking.order-result', compact('order', 'trackingData'));
    }

    /**
     * API endpoint for tracking
     */
    public function apiTrack(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:50',
            'courier' => 'nullable|string'
        ]);

        $trackingData = CourierTrackingService::trackPackage(
            $request->tracking_number,
            $request->courier
        );

        return response()->json([
            'success' => true,
            'data' => $trackingData
        ]);
    }

    /**
     * Track multiple packages
     */
    public function trackMultiple(Request $request)
    {
        $request->validate([
            'tracking_numbers' => 'required|array|max:10',
            'tracking_numbers.*' => 'required|string|max:50'
        ]);

        $trackingData = CourierTrackingService::trackMultiplePackages($request->tracking_numbers);

        if ($request->ajax()) {
            return response()->json($trackingData);
        }

        return view('tracking.multiple-results', compact('trackingData'));
    }

    /**
     * Get courier auto-detection
     */
    public function detectCourier(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:50'
        ]);

        $trackingNumber = $request->tracking_number;
        
        // This will auto-detect the courier
        $trackingData = CourierTrackingService::trackPackage($trackingNumber);

        return response()->json([
            'courier' => $trackingData['courier'],
            'tracking_number' => $trackingNumber
        ]);
    }
}
