<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->unique();
            $table->string('alternate_phone')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('pincode');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            
            // Vehicle Information
            $table->enum('vehicle_type', ['bike', 'scooter', 'bicycle', 'car', 'auto']);
            $table->string('vehicle_number');
            $table->string('license_number');
            $table->date('license_expiry');
            $table->string('vehicle_rc_number')->nullable();
            $table->string('insurance_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            
            // Documents
            $table->string('profile_photo')->nullable();
            $table->string('license_photo')->nullable();
            $table->string('vehicle_photo')->nullable();
            $table->string('aadhar_number');
            $table->string('aadhar_photo')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('pan_photo')->nullable();
            
            // Bank Details for payments
            $table->string('bank_account_holder')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc_code')->nullable();
            $table->string('bank_name')->nullable();
            
            // Status and verification
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended', 'inactive'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_online')->default(false);
            $table->boolean('is_available')->default(false);
            
            // Location tracking
            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->string('current_address')->nullable();
            
            // Performance metrics
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->integer('total_orders')->default(0);
            $table->integer('completed_orders')->default(0);
            $table->integer('cancelled_orders')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->decimal('this_month_earnings', 10, 2)->default(0);
            
            // Working preferences
            $table->json('working_hours')->nullable(); // Store start/end times for each day
            $table->integer('max_delivery_distance')->default(10); // in km
            $table->boolean('cash_on_delivery_enabled')->default(true);
            $table->boolean('online_payment_enabled')->default(true);
            
            // Emergency contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            
            // Admin notes and timestamps
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'is_available', 'is_online']);
            $table->index(['city', 'is_available']);
            $table->index(['current_latitude', 'current_longitude']);
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_partners');
    }
};
