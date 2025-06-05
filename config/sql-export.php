<?php

declare(strict_types=1);

return [
    /**
     * This is the path to the Laravel migrations directory.
     */
    'laravel_migrations_path' => env('SQL_EXPORT_LARAVEL_MIGRATIONS_PATH', base_path() . '/database/migrations'),

    /**
     * The export tool exports the Laravel migrations to SQL migration in this directory.
     */
    'sql_migrations_path' => env('SQL_EXPORT_SQL_MIGRATIONS_PATH', base_path() . '/database/sql'),

    /**
     * Queries to be excluded from the export.
     *
     * The queries will be excluded from the export if the query starts with one of the strings in this array.
     */
    'sql_excluded_queries' => [
        'select * from information_schema.tables',
        'select "migration" from "migrations"',
        'select max("batch") as aggregate from "migrations"',
        'create table "migrations" (',
        'insert into "migrations',
        'select * from "migrations"',
        'select exists (select 1 from pg_class c, pg_namespace n where n.nspname = \'public\' and c.relname = \'migrations\' and c.relkind in (\'r\', \'p\') and n.oid = c.relnamespace)',
    ]
];
