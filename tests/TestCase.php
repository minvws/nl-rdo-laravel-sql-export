<?php

declare(strict_types=1);

namespace MinVWS\SqlExporter\Tests;

use Illuminate\Support\Facades\Schema;
use MinVWS\SqlExporter\SqlExporterServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SqlExporterServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'pgsql',
            'host'     => env('DB_HOST', 'localhost'),
            'port'     => env('DB_PORT', '55322'),
            'database' => env('DB_DATABASE', 'db_test'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', 'password'),
        ]);
    }

    protected function cleanupDbTables(): void
    {
        Schema::dropAllTables();
    }
}
