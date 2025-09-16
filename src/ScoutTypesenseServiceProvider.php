<?php

namespace Ympact\Typesense;

use Illuminate\Support\ServiceProvider;

class ScoutTypesenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge the package configuration with the application's copy.
        $this->mergeConfigFrom(__DIR__.'/../config/scout-typesense.php', 'scout-typesense');
    }

    public function boot(): void
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__.'/../config/scout-typesense.php' => config_path('scout-typesense.php'),
        ], 'scout-typesense-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/ympact/scout-typesense'),
        ], 'scout-typesense');

        // Register the commands
        $this->bootCommands();
    }
    
    public function bootCommands()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([

        ]);
    }
}