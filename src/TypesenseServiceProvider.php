<?php

namespace Ympact\Typesense;

use Exception;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Typesense\Client as TypesenseClient;
// use Ympact\Typesense\Client\Client as TypesenseClient;
use Ympact\Typesense\Services\TypesenseEngine;

class TypesenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge the package configuration with the application's copy.
        $this->mergeConfigFrom(__DIR__.'/../config/typesense.php', 'typesense');

        // Bind Typesense Client
        $this->app->singleton(TypesenseClient::class, function ($app) {
            $config = config('scout.typesense');

            return new TypesenseClient($config['client-settings']);
        });

        resolve(EngineManager::class)->extend('typesense-extended', function () {
            $config = config('scout.typesense');

            if (! class_exists(TypesenseClient::class)) {
                throw new Exception('Please install the suggested Typesense client: typesense/typesense-php.');
            }
            $client = app(TypesenseClient::class);

            return new TypesenseEngine(
                $client,
                // (new TypesenseClient($config['client-settings']),
                $config['max_total_results'] ?? 1000
            );
        });
    }

    public function boot(): void
    {
        $this->bootCommands();
    }

    public function bootCommands()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            \Ympact\Typesense\Console\Commands\UpdateAllSchemas::class,
            \Ympact\Typesense\Console\Commands\RemoveOrphans::class,
        ]);
    }
}
