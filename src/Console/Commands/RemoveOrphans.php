<?php

namespace Ympact\Typesense\Console\Commands;

use Illuminate\Console\Command;
use Ympact\Typesense\Services\TypesenseEngine;
use Ympact\Typesense\Services\TypesenseSchemaManager;

class RemoveOrphans extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'typesense:remove-orphans';

    /**
     * The console command description.
     */
    protected $description = 'Remove all orphaned documents from Typesense collections.';

    protected $progressBar;

    /**
     * Execute the console command.
     */
    public function handle(TypesenseSchemaManager $schemaManager, TypesenseEngine $engine): int
    {

        try {
            $models = $schemaManager->getAllSearchableModels();
            $modelsWithOrphans = $models->filter(function ($modelClass) {
                $model = app()->make($modelClass);
                $orphanedDocuments = $model->getOrphanedDocuments();

                return $orphanedDocuments && $orphanedDocuments->isNotEmpty();
            });

            if ($modelsWithOrphans->isEmpty()) {
                $this->info('No orphaned documents found in any collections.');

                return Command::SUCCESS;
            }

            $this->info('Found '.$modelsWithOrphans->count().' collections with orphaned documents.');

            foreach ($modelsWithOrphans as $modelClass) {
                $model = app()->make($modelClass);
                $orphanedDocs = $model->getOrphanedDocuments();

                if (! $orphanedDocs->isEmpty()) {
                    $this->components->task(
                        'Removing: '.count($orphanedDocs).' for model: '.$modelClass,
                        fn () => $orphanedDocs->each(function ($id) use ($model) {
                            typesense()->deleteItem($model, $id);
                        })
                    );
                }
            }

            $this->info('All orphaned documents succesfully removed.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to remove orphans: {$e->getMessage()}");
            dump($e);

            // Wait for Scout queue to clear even on failure
            // $this->waitForQueueToClear();

            return Command::FAILURE;
        }
    }
}
