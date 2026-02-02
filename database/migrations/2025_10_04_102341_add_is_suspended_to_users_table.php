<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // In the migration file
// In migration
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('is_suspended')->default(false)->after('role');
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('is_suspended');
    });
}
};
