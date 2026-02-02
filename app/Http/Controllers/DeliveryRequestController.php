<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryRequest;
use App\Models\DeliveryPartner;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class DeliveryRequestController extends Controller
{
    public function __construct()
    {
        // Apply middleware in routes instead for better control
    }

    /**
     * Display delivery requests for the authenticated delivery partner
     */
    public function index()
    {
        $deliveryPartner = Auth::guard('delivery_partner')->user();
        
        // Get nearby pending requests
        $nearbyRequests = collect();
        if ($deliveryPartner->current_latitude && $deliveryPartner->current_longitude) {
            $nearbyRequests = DeliveryRequest::where('status', 'pending')
                ->where('expires_at', '>', now())
                ->get()
                ->filter(function ($request) use ($deliveryPartner) {
                    $distance = DeliveryRequest::calculateDistance(
                        $deliveryPartner->current_latitude,
                        $deliveryPartner->current_longitude,
                        $request->pickup_latitude,
                        $request->pickup_longitude
                    );
                    return $distance <= 5; // Within 5km radius
                })
                ->sortBy(function ($request) use ($deliveryPartner) {
                    return DeliveryRequest::calculateDistance(
                        $deliveryPartner->current_latitude,
                        $deliveryPartner->current_longitude,
                        $request->pickup_latitude,
                        $request->pickup_longitude
                    );
                });
        }

        // Get partner's accepted requests
        $acceptedRequests = DeliveryRequest::where('delivery_partner_id', $deliveryPartner->id)
            ->active()
            ->with('order')
            ->orderBy('requested_at', 'desc')
            ->get();

        // Get completed requests history
        $completedRequests = DeliveryRequest::where('delivery_partner_id', $deliveryPartner->id)
            ->completed()
            ->with('order')
            ->orderBy('delivered_at', 'desc')
            ->limit(20)
            ->get();

        return view('delivery_partner.requests.index', compact(
            'nearbyRequests', 
            'acceptedRequests', 
            'completedRequests'
        ));
    }

    /**
     * Accept a delivery request
     */
    public function accept(Request $request, DeliveryRequest $deliveryRequest)
    {
        $deliveryPartner = Auth::guard('delivery_partner')->user();

        // Validate partner can accept requests
        if (!$deliveryPartner->is_online || $deliveryPartner->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'You must be online and available to accept requests.'
            ]);
        }

        // Check if request is still available
        if ($deliveryRequest->status !== 'pending' || $deliveryRequest->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'This delivery request is no longer available.'
            ]);
        }

        // Check distance
        $distance = DeliveryRequest::calculateDistance(
            $deliveryPartner->current_latitude,
            $deliveryPartner->current_longitude,
            $deliveryRequest->pickup_latitude,
            $deliveryRequest->pickup_longitude
        );

        if ($distance > 5) {
            return response()->json([
                'success' => false,
                'message' => 'You are too far from the pickup location.'
            ]);
        }

        // Accept the request
        if ($deliveryRequest->acceptRequest($deliveryPartner->id)) {
            // Update partner status to busy
            $deliveryPartner->update(['status' => 'busy']);

            return response()->json([
                'success' => true,
                'message' => 'Delivery request accepted successfully!',
                'redirect' => route('delivery-partner.requests.show', $deliveryRequest)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to accept delivery request.'
        ]);
    }

    /**
     * Show specific delivery request details
     */
    public function show(DeliveryRequest $deliveryRequest)
    {
        $deliveryPartner = Auth::guard('delivery_partner')->user();

        // Ensure partner can view this request
        if ($deliveryRequest->delivery_partner_id !== $deliveryPartner->id && $deliveryRequest->status === 'pending') {
            // Allow viewing pending requests within range
            $distance = DeliveryRequest::calculateDistance(
                $deliveryPartner->current_latitude,
                $deliveryPartner->current_longitude,
                $deliveryRequest->pickup_latitude,
                $deliveryRequest->pickup_longitude
            );

            if ($distance > 5) {
                abort(403, 'You cannot view this delivery request.');
            }
        } elseif ($deliveryRequest->delivery_partner_id !== $deliveryPartner->id) {
            abort(403, 'You cannot view this delivery request.');
        }

        return view('delivery_partner.requests.show', compact('deliveryRequest'));
    }

    /**
     * Mark order as picked up
     */
    public function pickup(DeliveryRequest $deliveryRequest)
    {
        $deliveryPartner = Auth::guard('delivery_partner')->user();

        if ($deliveryRequest->delivery_partner_id !== $deliveryPartner->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ]);
        }

        if ($deliveryRequest->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request status for pickup.'
            ]);
        }

        if ($deliveryRequest->markPickedUp()) {
            return response()->json([
                'success' => true,
                'message' => 'Order marked as picked up successfully!',
                'status' => 'picked_up'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to mark order as picked up.'
        ]);
    }

    /**
     * Complete delivery and add payment to wallet
     */
    public function complete(DeliveryRequest $deliveryRequest)
    {
        $deliveryPartner = Auth::guard('delivery_partner')->user();

        if ($deliveryRequest->delivery_partner_id !== $deliveryPartner->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ]);
        }

        if ($deliveryRequest->status !== 'picked_up') {
            return response()->json([
                'success' => false,
                'message' => 'Order must be picked up before completing delivery.'
            ]);
        }

        if ($deliveryRequest->completeDelivery()) {
            // Update partner status back to available
            $deliveryPartner->update(['status' => 'available']);

            return response()->json([
                'success' => true,
                'message' => 'Delivery completed successfully! ₹25 added to your wallet.',
                'redirect' => route('delivery-partner.dashboard')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to complete delivery.'
        ]);
    }

    /**
     * Cancel delivery request
     */
    public function cancel(Request $request, DeliveryRequest $deliveryRequest)
    {
        $deliveryPartner = Auth::guard('delivery_partner')->user();

        if ($deliveryRequest->delivery_partner_id !== $deliveryPartner->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ]);
        }

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        if ($deliveryRequest->cancelRequest($request->reason)) {
            // Update partner status back to available
            $deliveryPartner->update(['status' => 'available']);

            return response()->json([
                'success' => true,
                'message' => 'Delivery request cancelled.',
                'redirect' => route('delivery-partner.requests.index')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to cancel delivery request.'
        ]);
    }

    /**
     * Update delivery partner location for proximity matching
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        $deliveryPartner = Auth::guard('delivery_partner')->user();
        
        $deliveryPartner->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'last_location_update' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully.'
        ]);
    }
}
