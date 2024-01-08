<?php

declare(strict_types=1);

return [
    /**
     * The name of the generated migration
     */
    'outputMigrationName' => env('OUTPUT_MIGRATION_NAME'),

    /**
     * The client ID of the OpenID Connect provider.
     */
    'laravelMigrationsPath' => env('LARAVEL_MIGRATIONS_PATH', getcwd() . '/database/migrations'),

    /**
     * If needed, the client secret of the OpenID Connect provider.
     */
    'sqlMigrationsPath' => env('SQL_MIGRATIONS_PATH', getcwd() . '/database/sql'),
];
