<?php

declare(strict_types=1);

namespace MinVWS\SqlExporter;

use Illuminate\Support\ServiceProvider;
use MinVWS\SqlExporter\Services\ExportMigrationService;

class SqlExporterServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sql-export.php', 'sql-export');

        $this->app->singleton(ExportMigrationService::class, function ($app) {
            $repository = $app['migration.repository'];

            return new ExportMigrationService(
                $repository,
                $app['db'],
                $app['files'],
                $app['events'],
                config('sql-export.laravel_migrations_path'),
                config('sql-export.sql_migrations_path'),
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
