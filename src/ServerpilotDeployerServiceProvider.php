<?php

namespace Riclep\ServerpilotDeployer;

use Illuminate\Support\ServiceProvider;
use Riclep\ServerpilotDeployer\Commands\BwiHosting;

class ServerpilotDeployerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'serverpilot-deployer');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'serverpilot-deployer');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('serverpilot-deployer.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/serverpilot-deployer'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/serverpilot-deployer'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/serverpilot-deployer'),
            ], 'lang');*/

            // Registering package commands.
            $this->commands([
            	BwiHosting::class
			]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'serverpilot-deployer');

        // Register the main class to use with the facade
        $this->app->singleton('serverpilot-deployer', function () {
            return new ServerpilotDeployer;
        });
    }
}
