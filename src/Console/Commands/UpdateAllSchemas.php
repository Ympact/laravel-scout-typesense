<?php

namespace Ympact\Typesense\Console\Commands;

use Illuminate\Console\Command;
use Ympact\Typesense\Services\TypesenseEngine;
use Ympact\Typesense\Services\TypesenseSchemaManager;

class UpdateAllSchemas extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'typesense:update-schemas {--force}';

    /**
     * The console command description.
     */
    protected $description = 'Update all Typesense schemas with zero downtime.';

    /**
     * Execute the console command.
     */
    public function handle(TypesenseSchemaManager $schemaManager, TypesenseEngine $engine): int
    {

        try {
            $models = $schemaManager->getAllSearchableModels();
            $this->info('Found '.$models->count().' typesense schemas.');
            $updatable = $models->filter(function ($modelClass) use ($schemaManager) {
                return $schemaManager->isUpdatable($modelClass);
            });

            if ($updatable->isEmpty()) {
                $this->info('No schemas need to be updated.');

                return Command::SUCCESS;
            }

            $this->info($updatable->count().' are outdated and will be udpated.');

            foreach ($updatable as $modelClass) {
                $this->components->task(
                    'Updating schema for model: '.$modelClass,
                    fn () => $schemaManager->updateSchema($modelClass, $this->option('force'), $this->getOutput())
                );
            }

            $this->info('All schemas created or updated successfully.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to update schemas: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
