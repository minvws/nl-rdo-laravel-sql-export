Auto-generated README for nl-rdo-laravel-sql-export


# Tests
A running postgresql database is required to run the tests.
To create the database run the following command:
```bash
docker run --name laravel-sql-export-test-postgres -v "$(pwd)/tests/fixtures/init.sql:/docker-entrypoint-initdb.d/10-create-testing-database.sql" -e POSTGRES_PASSWORD=password -p 55322:5432 -d postgres || docker start laravel-sql-export-test-postgres ||  echo "Unable to start postgres container, it may already be running"
```
