<?php

declare(strict_types=1);

namespace MinVWS\SqlExporter;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use MinVWS\SqlExporter\Services\ExportMigrationService;

class SqlExporterServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sql-export.php', 'sql-export');

        $this->app->singleton(ExportMigrationService::class, function (Application $app) {
            $repository = $app['migration.repository'];
            $config = $app['config'];

            return new ExportMigrationService(
                repository: $repository,
                resolver: $app['db'],
                files: $app['files'],
                dispatcher: $app['events'],
                laravelMigrationsPath: $config->get('sql-export.laravel_migrations_path'),
                sqlMigrationsPath: $config->get('sql-export.sql_migrations_path'),
                sqlExcludedQueries: $config->get('sql-export.sql_excluded_queries', [])
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\ExportCommand::class,
            ]);
        }
    }
}
