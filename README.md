# Laravel SQL exporter
This package is a slim package that allows you to export your laravel database migrations to a sql file.

## Requirements
Before using the package, make sure you have the following requirements:
- PHP 8.1 or higher
- Laravel 10 or higher
- Composer

## Installation
You can install the package via composer:
```bash
composer require minvws/laravel-sql-exporter
```

## Usage
After installing the package, you can run the following command to export your migrations to a sql file:

```bash
 vendor/bin/sail artisan sql-export
```

By default, the laravel migrations are read from the `database/migrations` folder of the working directory.
By default, the sql file will be saved in the `database/sql` folder of the working directory.

You can specify the laravel migrations and the output location by adding 
the `laravelMigrationsPath` or the `sqlMigrationsPath` arguments:

```bash
 vendor/bin/sail artisan sql-export --laravelMigrationsPath=/path/to/laravel/migrations --sqlMigrationsPath=/path/to/sql/migrations
```

## Development
A running postgresql database is required to run the tests.
To create the database run the following command:
```bash
docker run --name laravel-sql-export-test-postgres -v "$(pwd)/tests/fixtures/init.sql:/docker-entrypoint-initdb.d/10-create-testing-database.sql" -e POSTGRES_PASSWORD=password -p 55322:5432 -d postgres || docker start laravel-sql-export-test-postgres ||  echo "Unable to start postgres container, it may already be running"
```
