<?php

declare(strict_types=1);

namespace MinVWS\SqlExporter\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use MinVWS\SqlExporter\Services\ExportMigrationService;

class ExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sql-export {outputMigrationName} {laravelMigrationsPath?} {sqlMigrationsPath?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sql output for the laravel migrations';

    /**
     * @throws BindingResolutionException
     */
    public function handle(Application $app): void
    {
        $working_dir = getcwd();

        $outputMigrationName = $this->argument('outputMigrationName') . '.sql';

        $laravelMigrationsPath = $working_dir . '/database/migrations';
        if ($this->argument('laravelMigrationsPath')) {
            $laravelMigrationsPath = $this->argument('laravelMigrationsPath');
        }

        $sqlMigrationsPath = $working_dir . '/database/sql';
        if ($this->argument('sqlMigrationsPath')) {
            $sqlMigrationsPath = $this->argument('sqlMigrationsPath');
        }

        $app->singleton('exportMigrationService', function ($app)
            use ($outputMigrationName, $laravelMigrationsPath, $sqlMigrationsPath){
            $repository = $app['migration.repository'];

            return new ExportMigrationService(
                $repository,
                $app['db'],
                $app['files'],
                $app['events'],
                $outputMigrationName,
                $laravelMigrationsPath,
                $sqlMigrationsPath
            );
        });

        $migrationService = $app->make('exportMigrationService');
        $migrationService->setOutput($this->output);
        $migrationService->migrateToOutputFile();
    }
}
