<?php

declare(strict_types=1);

namespace MinVWS\SqlExporter\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class SqlExportCommandTest extends TestCase
{
    /**
     * @var string
     */
    private $laravelMigrationsPath;

    /**
     * @var string
     */
    private $sqlMigrationsPath;

    public function setUp(): void
    {
        parent::setUp();
        $this->laravelMigrationsPath = sys_get_temp_dir() . '/sql-exporter-migrations';
        $this->sqlMigrationsPath = sys_get_temp_dir() . '/sql-exporter-sql';
        File::deleteDirectory($this->laravelMigrationsPath);
        File::deleteDirectory($this->sqlMigrationsPath);
        mkdir($this->laravelMigrationsPath);
        mkdir($this->sqlMigrationsPath);
        DB::statement('DROP TABLE IF EXISTS "table";');
        DB::statement('DROP TABLE IF EXISTS "migrations";');
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return ['MinVWS\SqlExporter\SqlExporterServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
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

    public function testSqlExportCommandWhenNoMigrationsRan()
    {
        Carbon::setTestNow(Carbon::create(2023, 11, 28, 15, 8, 59));
        copy(
            'tests/fixtures/database/migrations/2023_09_06_150000_laravel_test_first_migration.php',
            $this->laravelMigrationsPath . '/2023_09_06_150000_laravel_test_first_migration.php'
        );
        $this->artisan('sql-export', [
            // Pass any command options or arguments here
            'outputMigrationName' => 'first_migration',
            '--laravelMigrationsPath' => $this->laravelMigrationsPath,
            '--sqlMigrationsPath' => $this->sqlMigrationsPath,
        ])->assertExitCode(0);

        $outputMigrations = scandir($this->sqlMigrationsPath);
        $this->assertCount(3, $outputMigrations);
        $this->assertEquals('2023_11_28_150859_first_migration.sql', $outputMigrations[2]);
        $this->assertEquals(
            file_get_contents('tests/fixtures/database/sql/2023_11_28_150859_first_migration.sql'),
            file_get_contents($this->sqlMigrationsPath . '/2023_11_28_150859_first_migration.sql')
        );
        $this->assertEquals(
            '2023_09_06_150000_laravel_test_first_migration.php',
            file_get_contents($this->laravelMigrationsPath . '/current_migration.txt'),
        );
    }

    public function testSqlExportCommandWhenFirstMigrationsRan()
    {
        Carbon::setTestNow(Carbon::create(2023, 11, 28, 15, 8, 59));
        copy(
            'tests/fixtures/database/migrations/2023_09_06_150000_laravel_test_first_migration.php',
            $this->laravelMigrationsPath . '/2023_09_06_150000_laravel_test_first_migration.php'
        );
        $this->artisan('sql-export', [
            'outputMigrationName' => 'first_migration',
            '--laravelMigrationsPath' => $this->laravelMigrationsPath,
            '--sqlMigrationsPath' => $this->sqlMigrationsPath,
        ])->assertExitCode(0);

        Carbon::setTestNow(Carbon::create(2024, 11, 28, 15, 8, 59));

        copy(
            'tests/fixtures/database/migrations/2024_09_06_150000_laravel_test_second_migration.php',
            $this->laravelMigrationsPath . '/2024_09_06_150000_laravel_test_second_migration.php'
        );
        copy(
            'tests/fixtures/database/migrations/current_migration.txt',
            $this->laravelMigrationsPath . '/current_migration.txt'
        );
        $this->artisan('sql-export', [
            'outputMigrationName' => 'second_migration',
            '--laravelMigrationsPath' => $this->laravelMigrationsPath,
            '--sqlMigrationsPath' => $this->sqlMigrationsPath,
        ])->assertExitCode(0);

        $outputMigrations = scandir($this->sqlMigrationsPath);
        $this->assertCount(4, $outputMigrations);
        $this->assertEquals('2024_11_28_150859_second_migration.sql', $outputMigrations[3]);
        $this->assertEquals(
            file_get_contents('tests/fixtures/database/sql/2024_11_28_150859_second_migration.sql'),
            file_get_contents($this->sqlMigrationsPath . '/2024_11_28_150859_second_migration.sql')
        );
        $this->assertEquals(
            '2024_09_06_150000_laravel_test_second_migration.php',
            file_get_contents($this->laravelMigrationsPath . '/current_migration.txt'),
        );
    }
}
