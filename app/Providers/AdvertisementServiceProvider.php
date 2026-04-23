<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Advertisement;

class AdvertisementServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('layouts.client', function ($view) {
            $advertisements = Advertisement::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            
            $view->with('tickerAdvertisements', $advertisements);
        });

        // Also share with admin layout for settings page
        View::composer('dashboard.settings', function ($view) {
            $advertisements = Advertisement::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            
            $view->with('advertisements', $advertisements);
        });
    }
}
