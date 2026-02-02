<?php

/**
 * Test Welcome and Purchase Notifications
 * 
 * This script tests:
 * 1. Buyer welcome notification (email + SMS)
 * 2. Seller welcome notification (email + SMS)
 * 3. Buyer purchase confirmation (email + SMS)
 * 4. Seller new order notification (email + SMS)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\BuyerWelcome;
use App\Notifications\SellerWelcome;
use App\Notifications\BuyerPurchaseConfirmation;
use App\Notifications\SellerNewOrder;
use Illuminate\Support\Facades\Log;

echo "🧪 Testing Grabbaskets Notification System\n";
echo str_repeat("=", 60) . "\n\n";

// Test 1: Buyer Welcome Notification
echo "📧 Test 1: Buyer Welcome Notification\n";
echo str_repeat("-", 60) . "\n";

try {
    // Get a buyer user
    $buyer = User::where('role', 'buyer')->first();
    
    if (!$buyer) {
        echo "❌ No buyer found in database\n\n";
    } else {
        echo "Testing with buyer: {$buyer->name} ({$buyer->email})\n";
        if ($buyer->phone) {
            echo "Phone: {$buyer->phone}\n";
        }
        
        // Send welcome notification
        $buyer->notify(new BuyerWelcome());
        echo "✅ Buyer welcome notification sent successfully!\n";
        echo "   - Email sent to: {$buyer->email}\n";
        if ($buyer->phone) {
            echo "   - SMS sent to: {$buyer->phone}\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Seller Welcome Notification
echo "📧 Test 2: Seller Welcome Notification\n";
echo str_repeat("-", 60) . "\n";

try {
    // Get a seller user
    $seller = User::where('role', 'seller')->first();
    
    if (!$seller) {
        echo "❌ No seller found in database\n\n";
    } else {
        echo "Testing with seller: {$seller->name} ({$seller->email})\n";
        if ($seller->phone) {
            echo "Phone: {$seller->phone}\n";
        }
        
        // Send welcome notification
        $seller->notify(new SellerWelcome());
        echo "✅ Seller welcome notification sent successfully!\n";
        echo "   - Email sent to: {$seller->email}\n";
        if ($seller->phone) {
            echo "   - SMS sent to: {$seller->phone}\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Buyer Purchase Confirmation
echo "📧 Test 3: Buyer Purchase Confirmation\n";
echo str_repeat("-", 60) . "\n";

try {
    // Get a recent order with relationships
    $order = Order::with(['product', 'buyerUser', 'sellerUser'])
        ->orderBy('created_at', 'desc')
        ->first();
    
    if (!$order) {
        echo "❌ No orders found in database\n\n";
    } else {
        $buyer = $order->buyerUser;
        $product = $order->product;
        
        if (!$buyer || !$product) {
            echo "❌ Order missing buyer or product data\n\n";
        } else {
            echo "Testing with order: #{$order->id}\n";
            echo "Buyer: {$buyer->name} ({$buyer->email})\n";
            echo "Product: {$product->name}\n";
            echo "Amount: ₹" . number_format($order->amount, 2) . "\n";
            
            // Send purchase confirmation
            $buyer->notify(new BuyerPurchaseConfirmation($order, $product));
            echo "✅ Purchase confirmation sent successfully!\n";
            echo "   - Email sent to: {$buyer->email}\n";
            if ($buyer->phone) {
                echo "   - SMS sent to: {$buyer->phone}\n";
            }
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Seller New Order Notification
echo "📧 Test 4: Seller New Order Notification\n";
echo str_repeat("-", 60) . "\n";

try {
    // Get a recent order with relationships
    $order = Order::with(['product', 'buyerUser', 'sellerUser'])
        ->orderBy('created_at', 'desc')
        ->first();
    
    if (!$order) {
        echo "❌ No orders found in database\n\n";
    } else {
        $seller = $order->sellerUser;
        $product = $order->product;
        
        if (!$seller || !$product) {
            echo "❌ Order missing seller or product data\n\n";
        } else {
            echo "Testing with order: #{$order->id}\n";
            echo "Seller: {$seller->name} ({$seller->email})\n";
            echo "Product: {$product->name}\n";
            echo "Amount: ₹" . number_format($order->amount, 2) . "\n";
            
            // Send new order notification
            $seller->notify(new SellerNewOrder($order, $product));
            echo "✅ New order notification sent successfully!\n";
            echo "   - Email sent to: {$seller->email}\n";
            if ($seller->phone) {
                echo "   - SMS sent to: {$seller->phone}\n";
            }
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "✅ All notification tests completed!\n\n";

echo "📋 Summary:\n";
echo "   - Check your email inbox for the test emails\n";
echo "   - Check your phone for SMS messages (if phone numbers configured)\n";
echo "   - Review logs: storage/logs/laravel.log\n";
echo "   - Check Twilio dashboard for SMS delivery status\n\n";

echo "💡 Next Steps:\n";
echo "   1. Test login flow - new users should get welcome messages\n";
echo "   2. Test purchase flow - buyers and sellers should get order notifications\n";
echo "   3. Verify email templates look good in various email clients\n";
echo "   4. Verify SMS messages are clear and concise\n";
