<?php

namespace App\Services;

use App\Models\DeliveryPartner;
use Illuminate\Support\Facades\Log;

class OrderAssignmentService
{
    /**
     * Automatically find and assign an available delivery partner to an order.
     * 
     * @param mixed $order The order model instance (Order, FoodOrder, or TenMinOrder)
     * @return DeliveryPartner|null
     */
    public function autoAssign($order)
    {
        try {
            // Find an online and available partner who doesn't have a current order
            $partner = DeliveryPartner::where('is_online', true)
                ->where('is_available', true)
                ->whereNull('current_order_id')
                ->first();

            if ($partner) {
                $partner->assignOrder($order);
                
                // Update order status to 'assigned'
                // Standard orders use 'delivery_status', others use 'status'
                if ($order instanceof \App\Models\Order) {
                    $order->update(['delivery_status' => 'assigned']);
                } else {
                    $order->update(['status' => 'assigned']);
                }

                Log::info("Order #{$order->id} auto-assigned to partner #{$partner->id} ({$partner->name})");
                return $partner;
            }

            Log::info("No available partners found for auto-assignment of Order #{$order->id}");
            return null;
        } catch (\Exception $e) {
            Log::error("Error during auto-assignment of Order #{$order->id}: " . $e->getMessage());
            return null;
        }
    }
}
