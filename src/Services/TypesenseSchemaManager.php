<?php

namespace Ympact\Typesense\Services;

use App\Traits\ConsoleOutputTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;
use Typesense\Client as Typesense;
use Ympact\Typesense\Traits\TypesenseClientTrait;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

// use Ympact\Typesense\Client\Client as Typesense;

class TypesenseSchemaManager
{
    use ConsoleOutputTrait;
    use TypesenseClientTrait;

    /**
     * The Typesense instance.
     */
    protected Typesense $typesense;

    /**
     * engine
     */
    protected TypesenseEngine $engine;

    protected bool $verbosity = false;

    protected bool $inConsole = false;

    public function __construct(Typesense $typesense, TypesenseEngine $engine)
    {
        if (app()->runningInConsole()) {
            $this->inConsole = true;
        }

        $this->typesense = $typesense;
        $this->engine = $engine;
    }

    /**
     * Summary of updateSchema
     *
     * @throws \Typesense\Exceptions\TypesenseClientError
     *
     * @todo: add support for configuring the schema update strategy (dual_writes)
     */
    public function updateSchema(string|Model $modelClass, bool $force = false, ?OutputInterface $output = null): bool
    {
        $model = $modelClass instanceof Model ? $modelClass : App::make($modelClass);

        if ($this->inConsole && $output) {
            $this->setOutput($output);
            $this->verbosity = $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
        }

        if (config('typesense.schema.dual_writes')) {
            return $this->updateZeroDowntime($model, $force);
        } else {
            return $this->updateSimple($model, $force);
        }
    }

    /**
     * Summary of updateSimple
     *
     * @param  mixed  $model
     * @param  bool  $force
     */
    public function updateSimple($model, $force = false): bool
    {
        // we going to update the schema directly
        // we need to get the difference between the old and new schemas to determine what to drop, what to add, and what to alter
        $modelSchemaName = $model->searchableAs();

        $oldSchema = $this->isCollection($modelSchemaName) ? $this->typesense->collections[$modelSchemaName]->retrieve() : null;

        /**
         * @var \Ympact\Typesense\Schema\Blueprint $newSchema
         */
        $newSchema = $model->typesenseCollectionSchema();

        if ($oldSchema) {
            $diffSchema = [
                'fields' => [],
            ];

            // check for fields that should be added or altered
            /**
             * @var \Ympact\Typesense\Schema\Field $newField
             */
            foreach ($newSchema->getFields() as $newField) {
                $oldField = collect($oldSchema['fields'])->firstWhere('name', $newField->getName());
                if ($oldField) {
                    $diff = array_diff_assoc($newField->toArray(), $oldField);
                    // if there are differences, then we need to update the field
                    if (count($diff) > 0) {
                        $diffSchema['fields'][] = $diff;
                    }
                } else {
                    $diffSchema['fields'][] = $newField;
                }
            }
            // check for fields that should be dropped
            foreach ($oldSchema['fields'] as $oldField) {
                $newField = $newSchema->getFields()->firstWhere('name', $oldField['name']);
                if (! $newField) {
                    $diffSchema['fields'][] = ['name' => $oldField['name'], 'drop' => true];
                }
            }

            $this->typesense->collections[$modelSchemaName]->update($diffSchema);
        }
        // if we don't have an old schema, we should simple create a new collection
        else {
            $this->typesense->collections->create(array_merge($newSchema->toArray(), ['name' => $modelSchemaName]));

        }

        return true;
    }

    public function isUpdatable($model)
    {

        $model = is_string($model) ? App::make($model) : $model;
        $modelSchemaName = $model->searchableAs();

        // get the new schema and collection name
        /**
         * @var \Ympact\Typesense\Schema\Blueprint $newSchema
         */
        $newSchema = $model->typesenseCollectionSchema();
        $newVersion = $newSchema->getVersion() ?? null;

        // if alias is an actual collection and not an alias, we then need to remove the collection
        // use case when moving from direct collection calls to using aliases
        if ($this->isCollection($modelSchemaName)) {
            $this->typesense->collections[$modelSchemaName]->delete();
        }

        // get the old collection name from the alias
        $alias = $this->isAlias($modelSchemaName) ? $this->typesense->aliases[$modelSchemaName]->retrieve() : null;
        $oldCollectionName = $alias ? $alias['collection_name'] : null;
        $oldSchema = $oldCollectionName && $this->isCollection($oldCollectionName) ? $this->typesense->collections[$oldCollectionName]->retrieve() : null;

        // Check if schema has changed
        if ($oldSchema) {
            if ($newVersion && ! $this->hasNewVersion($oldSchema, $newVersion)) {
                // ($this->inConsole && $this->verbosity) ?: info("Schema for {$modelSchemaName} does not have a new version number. Skipping update.");

                return false;
            }

            if (! $newVersion && ! $this->hasSchemaChanged($oldSchema, $newSchema->toArray())) {
                // ($this->inConsole && $this->verbosity) ?: info("Schema for {$modelSchemaName} has not changed. Skipping update.");

                return false;
            }
        }

        return true;
    }

    /**
     * UpdateZeroDowntime
     *
     * @param  mixed  $model
     */
    public function updateZeroDowntime($model): bool
    {
        $modelSchemaName = $model->searchableAs();

        // get the new schema and collection name
        /**
         * @var \Ympact\Typesense\Schema\Blueprint $newSchema
         */
        $newSchema = $model->typesenseCollectionSchema();
        // $newVersion = $newSchema->getVersion() ?? null;

        // if alias is an actual collection and not an alias, we then need to remove the collection
        // use case when moving from direct collection calls to using aliases
        if ($this->isCollection($modelSchemaName)) {
            $this->typesense->collections[$modelSchemaName]->delete();
        }

        // get the old collection name from the alias
        $alias = $this->isAlias($modelSchemaName) ? $this->typesense->aliases[$modelSchemaName]->retrieve() : null;
        $oldCollectionName = $alias ? $alias['collection_name'] : null;
        // $oldSchema = $oldCollectionName && $this->isCollection($oldCollectionName) ? $this->typesense->collections[$oldCollectionName]->retrieve() : null;

        $suffix = $newSchema->getVersion() ?? now()->format('YmdHis');
        $newCollectionName = $modelSchemaName.'_'.$suffix;

        if ($oldCollectionName) {
            // Enable dual writes for the engine
            $this->engine->enableUpdatingSchema($modelSchemaName, $oldCollectionName, $newCollectionName);
        }

        // Create a new collection with the updated schema
        $this->typesense->collections->create(array_merge($newSchema->toArray(), ['name' => $newCollectionName]));

        // Reindex data into the new collection
        $model->cursor()->each(fn ($item) => $item->searchable());
        /*->chunk(100, function ($records) use ($newCollectionName) {
            dump(count($records));
            $records->searchable();
            //$documents = $records->map->toSearchableArray()->toArray();

            //$this->typesense->collections[$newCollectionName]->documents->import($documents, ['action' => 'upsert']);
        });
        */

        // Update alias to point to the new collection
        $this->typesense->aliases->upsert($modelSchemaName, ['collection_name' => $newCollectionName]);

        // Remove the old collection (optional, after verification)
        if ($oldCollectionName) {

            $this->typesense->collections[$oldCollectionName]->delete();

            // Disable dual writes for the engine
            $this->engine->disableUpdatingSchema($modelSchemaName);
        }

        return true;
    }

    /**
     * Summary of hasSchemaChanged
     */
    protected function hasSchemaChanged(array $oldSchema, array $newSchema): bool
    {
        // Remove fields that shouldn't be compared (e.g., name, version)
        unset($oldSchema['name'], $newSchema['name']);
        unset($oldSchema['metadata']['version'], $newSchema['metadata']['version']);

        return count(array_diff_assoc($oldSchema, $newSchema)) > 0;
    }

    /**
     * Summary of hasVersionChanged
     */
    protected function hasNewVersion(array $currentSchema, string $newVersion): bool
    {
        $currentVersion = $currentSchema['metadata']['version'] ?? null;

        // return true in case newversion is newer than the currentversion
        return $newVersion > $currentVersion;
    }

    /**
     * Summary of getOldCollection
     *
     * @return mixed
     */
    protected function getOldCollection(string $aliasName, string $newCollectionName): ?string
    {
        $alias = $this->typesense->aliases[$aliasName]->retrieve();
        $currentCollection = $alias['collection_name'] ?? null;

        return $currentCollection !== $newCollectionName ? $currentCollection : null;
    }

    // return all schemas
    // @todo: return collection of Schema objects not an array
    // only once all schemas have been updated
    public static function getSchemaDetails()
    {
        /**
         * @var \Illuminate\Support\Collection<array> $schemas
         */
        $schemas = collect();
        $models = self::getAllSearchableModels();
        foreach ($models as $modelClass) {
            $model = App::make($modelClass);
            $name = $model->searchableAs();
            $collection = typesense()->getCollections()[$name]->retrieve();
            $array = [
                'class' => get_class($model),
                'model' => $model,
                'module' => Str::of((new \ReflectionClass($model::class))->getNamespaceName())->after('Modules\\')->before('\Models')->toString(),
                'collection' => $name,
                'alias' => collect(typesense()->getAliases()->retrieve()['aliases'])->where('name', $name)->pluck('collection_name')->first() ?? null,
                'schema' => $model->typesenseCollectionSchema(),
                'count' => $collection['num_documents'],
                'created' => Carbon::createFromTimestamp($collection['created_at']),
                'version' => [
                    'collection' => $collection['metadata']['version'] ?? null,
                    'schema' => $model->typesenseCollectionSchema()->getVersion(),
                ],
                'collection_raw' => $collection,
            ];
            $schemas->push($array);
        }

        return $schemas;
    }

    /**
     * Summary of getAllSearchableModels
     *
     * @return \Illuminate\Support\Collection<string>
     */
    public static function getAllSearchableModels(): \Illuminate\Support\Collection
    {
        $modelDirectories = [
            app_path('Models'),
            app_path('Models/*'),
            base_path('Modules/*/app/Models'),
            base_path('Modules/*/app/Models/*'),
            base_path('Modules/*/app/Models/*/*'),
        ];

        $models = collect();

        foreach ($modelDirectories as $directory) {
            foreach (glob($directory.'/*.php') as $filePath) {

                $namespace = self::extractNamespace($filePath);
                if (! $namespace) {
                    continue;
                }

                $class = $namespace.'\\'.pathinfo($filePath, PATHINFO_FILENAME);

                if (class_exists($class)) {
                    $reflection = new \ReflectionClass($class);
                    $traits = self::getAllTraits($class);

                    if ($reflection->isSubclassOf('Illuminate\Database\Eloquent\Model') &&
                        in_array('Ympact\\Typesense\\Traits\\HasSearch', $traits) &&
                        ! $reflection->isAbstract()) {
                        $models->push($class);
                    }
                }
            }
        }

        return $models;
    }

    /**
     * Summary of getAllTraits
     */
    protected static function getAllTraits(string $class): array
    {
        $traits = class_uses($class);

        foreach ($traits as $trait) {
            $traits = array_merge($traits, self::getAllTraits($trait));
        }

        return $traits;
    }

    /**
     * Summary of extractNamespace
     */
    protected static function extractNamespace(string $filePath): ?string
    {

        $namespace = '';
        $file = fopen($filePath, 'r');
        while ($line = fgets($file)) {
            if (preg_match('/^namespace (.*);$/', $line, $matches)) {
                $namespace = $matches[1];
                break;
            }
        }
        fclose($file);

        return $namespace ?: null;

    }

    protected function scoutQueueHasPendingJobs(): bool
    {
        $queueName = config('queue.connections.scout.queue', 'scout'); // Default Scout queue name
        $connection = app('queue')->connection(config('queue.default'));

        return $connection->size($queueName) > 0;
    }

    protected function waitForQueueToClear(): void
    {
        info('Waiting for Scout queue to process all jobs...');
        $startTime = now();

        while ($this->scoutQueueHasPendingJobs()) {
            if (now()->diffInSeconds($startTime) > 600) { // Maximum 10 minutes
                error('Timeout: Scout queue is still processing jobs.');

                return;
            }

            sleep(5); // Wait for 5 seconds before checking again
        }

        info('Scout queue is empty. Proceeding...');
    }
}
