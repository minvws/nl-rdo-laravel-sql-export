<?php

declare(strict_types=1);

return [
    /**
     * The client ID of the OpenID Connect provider.
     */
    'laravel_migrations_path' => env('SQL_EXPORT_LARAVEL_MIGRATIONS_PATH', getcwd() . '/database/migrations'),

    /**
     * If needed, the client secret of the OpenID Connect provider.
     */
    'sql_migrations_path' => env('SQL_EXPORT_SQL_MIGRATIONS_PATH', getcwd() . '/database/sql'),
];
