<?php

use App\Models\DeliveryPartner;

// Update delivery partner status to active
$partner = DeliveryPartner::find(2);
if ($partner) {
    $partner->status = 'active';
    $partner->save();
    echo "Updated delivery partner status to active\n";
    echo "Partner details: ID {$partner->id}, Phone: {$partner->phone}, Status: {$partner->status}\n";
} else {
    echo "Delivery partner not found\n";
}