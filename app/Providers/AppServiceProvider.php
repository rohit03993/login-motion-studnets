<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // CRITICAL FIX: Manually register 'files' binding early
        // This must be done before EventServiceProvider tries to use it
        // Fixes "Target class [files] does not exist" error in Laravel 12
        if (!$this->app->bound('files')) {
            $this->app->singleton('files', function () {
                return new Filesystem();
            });
        }
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
