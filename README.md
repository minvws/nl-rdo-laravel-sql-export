# Laravel SQL exporter
This package is a slim package that allows you to export your Laravel database migrations to an SQL file.

## Requirements
Before using the package, make sure you have the following requirements:
- PHP 8.1 or higher
- Laravel 10 or higher
- Composer

## Installation
Install the package through composer. Since this is currently a private package, you must enable the repository in your 
`composer.json` file:

```json
{
    "repositories": {
        "minvws/laravel-sql-exporter": {
            "type": "vcs",
            "url": "git@github.com:minvws/nl-rdo-laravel-sql-exporter"
        }
    }
}
```
You can install the package via composer (as a `dev dependency`):
```bash
composer require --dev minvws/laravel-sql-exporter
```

## Usage
After installing the package, you can run the following command to export your migrations to an SQL file:

```bash
vendor/bin/sail artisan sql-export migration_description
```

By default, the laravel migrations are read from the `database/migrations` folder of the working directory.
By default, the SQL file will be saved in the `database/sql` folder of the working directory.

You can specify the laravel migrations and the output location by adding 
the `laravelMigrationsPath` or the `sqlMigrationsPath` arguments:

```bash
 vendor/bin/sail artisan sql-export migration_description \
 --laravelMigrationsPath=/path/to/laravel/migrations \
 --sqlMigrationsPath=/path/to/sql/migrations
```

## Development
A running PostgreSQL database is required to run the tests.
To create the database run the following command:
```bash
docker run --name laravel-sql-export-test-postgres -v "$(pwd)/tests/fixtures/init.sql:/docker-entrypoint-initdb.d/10-create-testing-database.sql" -e POSTGRES_PASSWORD=password -p 55322:5432 -d postgres || docker start laravel-sql-export-test-postgres ||  echo "Unable to start Postgres container, it may already be running"
```

## Contributing
If you encounter any issues or have suggestions for improvements, please feel free to open an issue or submit a pull request on the GitHub repository of this package.

## License
This package is open-source and released under the European Union Public License version 1.2. You are free to use, modify, and distribute the package in accordance with the terms of the license.

## Part of iCore
This package is part of the iCore project.
