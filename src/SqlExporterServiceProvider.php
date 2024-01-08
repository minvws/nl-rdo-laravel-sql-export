<?php

declare(strict_types=1);

namespace MinVWS\SqlExporter;

use Illuminate\Support\ServiceProvider;
use MinVWS\SqlExporter\Services\ExportMigrationService;

class SqlExporterServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sqlExport.php', 'sqlExport');

        $this->app->singleton(ExportMigrationService::class, function ($app) {
            $repository = $app['migration.repository'];

            return new ExportMigrationService(
                $repository,
                $app['db'],
                $app['files'],
                $app['events'],
                config('sqlExport.laravelMigrationsPath'),
                config('sqlExport.sqlMigrationsPath'),
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
