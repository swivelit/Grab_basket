<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryPartner;
use App\Notifications\DeliveryPartnerNotification;

class TestSmsNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sms {phone?} {--partner-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMS notification to delivery partner';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!config('services.sms.enabled')) {
            $this->error('SMS notifications are disabled. Enable in .env: SMS_ENABLED=true');
            return 1;
        }

        // Get partner by ID or phone
        $partnerId = $this->option('partner-id');
        $phone = $this->argument('phone');

        if ($partnerId) {
            $partner = DeliveryPartner::find($partnerId);
        } elseif ($phone) {
            $partner = DeliveryPartner::where('phone', $phone)->first();
        } else {
            // Get first partner
            $partner = DeliveryPartner::first();
        }

        if (!$partner) {
            $this->error('No delivery partner found');
            return 1;
        }

        $this->info("Sending test SMS to: {$partner->name} ({$partner->phone})");

        try {
            $partner->notify(new DeliveryPartnerNotification(
                'Test SMS',
                'This is a test SMS notification from GrabBaskets. If you received this, SMS notifications are working!',
                'info',
                config('app.url') . '/delivery-partner/dashboard',
                'Open Dashboard',
                ['send_email' => false]
            ));

            $this->info('✓ SMS notification sent successfully!');
            $this->info('Check the partner\'s phone and Laravel logs for details.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to send SMS: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
