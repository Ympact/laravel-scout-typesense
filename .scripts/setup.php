<?php
require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use function Laravel\Prompts\{error, info, text, select, confirm};

class Setup extends Command
{
    protected $signature = 'setup';
    protected $description = 'Setup the boilerplate package';
    protected $placeholders = [];

    public function handle()
    {
        // if the directory name is _boilerplate then we cancel the setup
        $workingDir = getcwd();
        $dir = Str::afterLast($workingDir, '/');

        if ( $dir === '_boilerplate') {
            error('Please rename the package directory before running the setup.');
            return 1;
        }

        // we will request the name for the package and rename the files and folders
        info('Setting up the boilerplate package...');

        $defaultVendor = Str::contains($dir, '-') 
            ? Str::of($dir)->before('-')->lower() 
            : Str::of($workingDir)->beforeLast('/')->afterLast('/')->lower();

        $defaultPackage = Str::of($workingDir)->after($defaultVendor . '/')->lower();

        $vendor = text('Enter the new vendor name:', default: $defaultVendor);
        $package = text('Enter the package name ('.$vendor.'/{package}):', default: $defaultPackage);

        $this->placeholders['{Package}'] = Str::studly($package);
        $this->placeholders['{package}'] = Str::lower($package);
        $this->placeholders['{vendor}'] = Str::lower($vendor);

        $this->placeholders['Boilerplate'] = Str::studly($package);
        $this->placeholders['boilerplate'] = Str::lower($package);


        $this->updateFileContents();
        $this->renameFilesAndFolders();


        if(confirm('initialize a new git repository?', default: true)) {
            exec('rm -rf .git');
            exec('git init');
            info('Initialized a new git repository.');

        }

        info('Setup completed.');
        info('you can use `./vendor/bin/testbench artisan` for running artisan commands.');

    }

    public function renameFilesAndFolders()
    {
        foreach ($this->filesToRename as $oldPath => $newPath) {
            $newPathResolved = strtr($newPath, $this->placeholders);
            if (file_exists($oldPath)) {
                rename($oldPath, $newPathResolved);
                info("Renamed file: $oldPath to $newPathResolved");
            } else {
                error("File not found: $oldPath");
            }
        }

        foreach ($this->foldersToRename as $oldFolder => $newFolder) {
            $newFolderResolved = strtr($newFolder, $this->placeholders);
            if (is_dir($oldFolder)) {
                rename($oldFolder, $newFolderResolved);
                info("Renamed folder: $oldFolder to $newFolderResolved");
            } else {
                error("Folder not found: $oldFolder");
            }
        }
    }

    public function updateFileContents()
    {
        foreach ($this->filesWithContentToUpdate as $filePath) {
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $newContent = strtr($content, $this->placeholders);
                file_put_contents($filePath, $newContent);
                info("Updated content in file: $filePath");
            } else {
                error("File not found for content update: $filePath");
            }
        }
    }

    protected $filesToRename = [
        'src/BoilerplateServiceProvider.php' => 'src/{Package}ServiceProvider.php',
        'config/boilerplate.php' => 'config/{package}.php',
        #'resources/views/welcome.blade.php' => 'resources/views/{vendor}/{package}/welcome.blade.php',
        'tests/Feature/FeatureTest.php' => 'tests/Feature/{Package}Test.php',
        'stubs/config/boilerplate.php' => 'stubs/config/{package}.php',

    ];

    protected $foldersToRename = [
        #'resources/views/ympact/boilerplate' => 'resources/views/{vendor}/{package}',
    ];

    protected $filesWithContentToUpdate = [
        'src/BoilerplateServiceProvider.php',
        'config/boilerplate.php',
        #'resources/views/welcome.blade.php',
        'tests/Feature/FeatureTest.php',
        'tests/Feature/CommandTest.php.bak',
        'src/helpers.php',
        'composer.json',
        'README.md',
        'docs/index.md',
        
    ];

}

$setup = new Setup();
$setup->handle();