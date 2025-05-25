<?php

namespace Aflorea4\NetopiaPayments;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class NetopiaPaymentsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration
        if (method_exists($this, 'publishes')) {
            $this->publishes([
                __DIR__ . '/../config/netopia.php' => dirname(__DIR__, 4) . '/config/netopia.php',
            ], 'config');
        }

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'netopia');

        // Publish views
        if (method_exists($this, 'publishes')) {
            $this->publishes([
                __DIR__ . '/../resources/views' => dirname(__DIR__, 4) . '/resources/views/vendor/netopia',
            ], 'views');
        }

        // Register routes for payment callbacks
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/netopia.php', 'netopia'
        );

        // Register the main class to use with the facade
        $this->app->singleton('netopia-payments', function () {
            return new \Aflorea4\NetopiaPayments\NetopiaPayments();
        });
    }
}
