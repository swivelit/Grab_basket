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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 10)->unique()->nullable()->after('wallet_point');
            }
            if (!Schema::hasColumn('users', 'referrer_id')) {
                $table->foreignId('referrer_id')->nullable()->constrained('users')->nullOnDelete()->after('referral_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->dropColumn(['referral_code', 'referrer_id']);
        });
    }
};
