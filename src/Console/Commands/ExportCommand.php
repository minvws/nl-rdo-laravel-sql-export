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
     * @param Application $app
     *
     * @throws BindingResolutionException
     */
    public function handle(Application $app): void
    {
        $working_dir = getcwd();

        $outputMigrationNameArgument = $this->argument('outputMigrationName');
        assert(is_string($outputMigrationNameArgument));
        $laravelMigrationsPathArgument = $this->argument('laravelMigrationsPath');
        assert(is_null($laravelMigrationsPathArgument) || is_string($laravelMigrationsPathArgument));
        $sqlMigrationsPathArgument = $this->argument('sqlMigrationsPath');
        assert(is_null($sqlMigrationsPathArgument) || is_string($sqlMigrationsPathArgument));

        $app->singleton('exportMigrationService', function ($app) use (
            $outputMigrationNameArgument,
            $laravelMigrationsPathArgument,
            $sqlMigrationsPathArgument,
            $working_dir
        ) {
            $repository = $app['migration.repository'];

            return new ExportMigrationService(
                $repository,
                $app['db'],
                $app['files'],
                $app['events'],
                $outputMigrationNameArgument . '.sql',
                $laravelMigrationsPathArgument ?? $working_dir . '/database/migrations',
                $sqlMigrationsPathArgument ?? $working_dir . '/database/sql',
            );
        });

        $migrationService = $app->make('exportMigrationService');
        $migrationService->setOutput($this->output);
        $migrationService->migrateToOutputFile();
    }
}
