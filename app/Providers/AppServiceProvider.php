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
        // Register helper function for punch_logs connection
        if (!function_exists('getPunchLogsConnection')) {
            require_once app_path('Helpers/DatabaseHelper.php');
        }
    }
}
