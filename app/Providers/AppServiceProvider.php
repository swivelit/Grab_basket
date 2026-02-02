<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share categories with all views for navbar modal
        view()->composer('*', function ($view) {
            try {
                $categories = \App\Models\Category::with('subcategories')->get();
            } catch (\Exception $e) {
                $categories = collect([]);
            }
            $view->with('categories', $categories);
        });
    }
}
