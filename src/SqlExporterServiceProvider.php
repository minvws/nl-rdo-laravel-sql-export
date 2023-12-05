<?php

declare(strict_types=1);

namespace MinVWS\SqlExporter;

use Illuminate\Support\ServiceProvider;

class SqlExporterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\ExportCommand::class,
            ]);
        }
    }
}
