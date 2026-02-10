<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations for optimizing guest search performance.
     * Creates composite indexes and full-text search capabilities.
     */
    public function up()
    {
        try {
            // Create composite index for image filtering (most common query in search)
            // This significantly speeds up the whereNotNull and image filtering logic
            DB::statement("
                CREATE INDEX idx_products_image_filter 
                ON products (category_id, created_at)
            ");
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
        
        try {
            // Create full-text search index for name and description
            // Replaces slow LIKE queries with fast full-text search
            DB::statement("
                ALTER TABLE products 
                ADD FULLTEXT INDEX products_name_description_fulltext (name, description)
            ");
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
        
        try {
            // Create index for seller searches - speeds up seller_id lookups
            DB::statement("
                CREATE INDEX idx_products_seller_search 
                ON products (seller_id, category_id, created_at)
            ");
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
        
        try {
            // Create index for price and discount filtering
            DB::statement("
                CREATE INDEX idx_products_filters 
                ON products (price, discount, delivery_charge)
            ");
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
        
        try {
            // Optimize sellers table for search - speeds up seller name/store searches
            DB::statement("
                CREATE INDEX idx_sellers_search 
                ON sellers (name, store_name, email)
            ");
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
        
        try {
            // Create index for users table to speed up seller email matching
            DB::statement("
                CREATE INDEX idx_users_email_search 
                ON users (email, id)
            ");
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("DROP INDEX idx_products_image_filter ON products");
        DB::statement("DROP INDEX products_name_description_fulltext ON products");
        DB::statement("DROP INDEX idx_products_seller_search ON products");
        DB::statement("DROP INDEX idx_products_filters ON products");
        DB::statement("DROP INDEX idx_sellers_search ON sellers");
        DB::statement("DROP INDEX idx_users_email_search ON users");
    }
};