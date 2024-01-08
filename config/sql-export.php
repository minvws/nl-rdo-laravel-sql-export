<?php

declare(strict_types=1);

return [
    /**
     * This is the path to the Laravel migrations directory.
     */
    'laravel_migrations_path' => env('SQL_EXPORT_LARAVEL_MIGRATIONS_PATH', getcwd() . '/database/migrations'),

    /**
     * The export tool exports the Laravel migrations to SQL migration in this directory.
     */
    'sql_migrations_path' => env('SQL_EXPORT_SQL_MIGRATIONS_PATH', getcwd() . '/database/sql'),
];
