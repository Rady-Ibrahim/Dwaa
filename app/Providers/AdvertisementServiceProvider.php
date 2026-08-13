<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Advertisement;
use App\Models\Setting;

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
            $tickerAdvertisements = Advertisement::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $tickerEnabled = Setting::get('ticker_enabled', '1') === '1';
            $tickerSpeed = (int) Setting::get('ticker_speed', '20');
            $tickerSpeed = max(5, min(60, $tickerSpeed));

            $view->with(compact('tickerAdvertisements', 'tickerEnabled', 'tickerSpeed'));
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
