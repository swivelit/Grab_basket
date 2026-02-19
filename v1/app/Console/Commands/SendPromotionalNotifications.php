<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Product;
use App\Models\Wishlist;

class SendPromotionalNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-promotions 
                            {--type=daily : Type of promotion (daily, weekly, flash-sale, weekend)}
                            {--user-type=buyers : Target users (all, buyers, sellers)}
                            {--email : Send via email instead of just notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Amazon-like promotional notifications to users (with optional email)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $userType = $this->option('user-type');
        $sendEmail = $this->option('email');

        $this->info("Sending {$type} promotional " . ($sendEmail ? 'emails' : 'notifications') . " to {$userType} users...");

        if ($sendEmail) {
            // Send promotional emails
            $sentCount = NotificationService::sendAutomatedPromotionalEmail($type, $userType);
            $this->info("Promotional emails sent to {$sentCount} users successfully!");
        } else {
            // Send regular notifications
            switch ($type) {
                case 'daily':
                    $this->sendDailyDeals();
                    break;
                case 'weekly':
                    $this->sendWeeklyNewsletter();
                    break;
                case 'flash-sale':
                    $this->sendFlashSaleAlert();
                    break;
                case 'weekend':
                    $this->sendWeekendSpecial();
                    break;
                case 'wishlist-sale':
                    $this->sendWishlistSaleAlerts();
                    break;
                case 'back-in-stock':
                    $this->sendBackInStockAlerts();
                    break;
                default:
                    $this->error("Unknown promotion type: {$type}");
                    return 1;
            }
            $this->info('Promotional notifications sent successfully!');
        }

        return 0;
    }

    private function sendDailyDeals()
    {
        $users = $this->getTargetUsers();
        $dealProducts = Product::where('discount', '>', 0)
                               ->where('stock', '>', 0)
                               ->inRandomOrder()
                               ->limit(5)
                               ->get();

        $title = "🔥 Daily Deals - Up to 50% Off!";
        $message = "Don't miss today's amazing deals! Limited time offers on top products.";

        NotificationService::sendBulkNotification(
            $users->pluck('id')->toArray(),
            'promotion',
            $title,
            $message,
            ['products' => $dealProducts->pluck('id')]
        );

        $this->info("Sent daily deals to {$users->count()} users");
    }

    private function sendWeeklyNewsletter()
    {
        $users = $this->getTargetUsers();
        $newProducts = Product::where('created_at', '>=', now()->subWeek())
                              ->where('stock', '>', 0)
                              ->count();

        $title = "📰 Weekly Update - {$newProducts} New Products Added!";
        $message = "Check out this week's new arrivals and trending products. Something special waiting for you!";

        NotificationService::sendBulkNotification(
            $users->pluck('id')->toArray(),
            'promotion',
            $title,
            $message,
            ['new_products_count' => $newProducts]
        );

        $this->info("Sent weekly newsletter to {$users->count()} users");
    }

    private function sendFlashSaleAlert()
    {
        $users = $this->getTargetUsers();
        
        $title = "⚡ FLASH SALE - 2 Hours Only!";
        $message = "Hurry! Flash sale ends in 2 hours. Extra 20% off on selected items. Shop now!";

        NotificationService::sendBulkNotification(
            $users->pluck('id')->toArray(),
            'promotion',
            $title,
            $message,
            ['sale_type' => 'flash', 'duration_hours' => 2]
        );

        $this->info("Sent flash sale alert to {$users->count()} users");
    }

    private function sendWeekendSpecial()
    {
        $users = $this->getTargetUsers();
        
        $title = "🎉 Weekend Special - Extra Savings Just for You!";
        $message = "Make your weekend special with our exclusive deals. Free delivery on all orders this weekend!";

        NotificationService::sendBulkNotification(
            $users->pluck('id')->toArray(),
            'promotion',
            $title,
            $message,
            ['sale_type' => 'weekend_special']
        );

        $this->info("Sent weekend special to {$users->count()} users");
    }

    private function sendWishlistSaleAlerts()
    {
        $wishlistItems = Wishlist::with(['user', 'product'])
                                ->whereHas('product', function($query) {
                                    $query->where('discount', '>', 0);
                                })
                                ->get();

        $sentCount = 0;
        foreach ($wishlistItems as $item) {
            NotificationService::sendWishlistItemOnSale($item->user, $item->product);
            $sentCount++;
        }

        $this->info("Sent wishlist sale alerts to {$sentCount} users");
    }

    private function sendBackInStockAlerts()
    {
        // This would typically be triggered when products are restocked
        // For demo, we'll send to users who have products in wishlist that are now in stock
        $wishlistItems = Wishlist::with(['user', 'product'])
                                ->whereHas('product', function($query) {
                                    $query->where('stock', '>', 0);
                                })
                                ->get();

        $sentCount = 0;
        foreach ($wishlistItems as $item) {
            NotificationService::sendProductBackInStock($item->user, $item->product);
            $sentCount++;
        }

        $this->info("Sent back in stock alerts to {$sentCount} users");
    }

    private function getTargetUsers()
    {
        $userType = $this->option('user-type');
        
        switch ($userType) {
            case 'buyers':
                return User::where('role', 'buyer')->get();
            case 'sellers':
                return User::where('role', 'seller')->get();
            default:
                return User::all();
        }
    }
}
