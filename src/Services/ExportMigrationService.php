<?php

declare(strict_types=1);

namespace MinVWS\SqlExporter\Services;

use Carbon\Carbon;
use Illuminate\Console\View\Components\Error;
use Illuminate\Console\View\Components\Info;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
class ExportMigrationService extends Migrator
{
    public function __construct(
        MigrationRepositoryInterface $repository,
        ConnectionResolverInterface $resolver,
        Filesystem $files,
        Dispatcher $dispatcher,
        private readonly string $outputMigrationName,
        private readonly string $laravelMigrationsPath,
        private readonly string $sqlMigrationsPath
    )
        
    {
        parent::__construct($repository, $resolver, $files, $dispatcher);
    }

    /**
     * @throws \Exception
     */
    public function migrateToOutputFile(): void
    {
        $files = $this->getMigrationFilesAfterCurrentMigration();

        $this->runDownMigrationsUntilCurrentMigration($files);

        DB::connection()->enableQueryLog();

        $this->runUpMigrations($files);

        $queries = $this->filterOutMigrationQueries(DB::getRawQueryLog());

        if (empty($queries)) {
            $this->write(Error::class, "No queries to write to SQL migration file.");
            return;
        }

        $this->writeMigrationsFile($this->outputMigrationName, $queries);

        $this->updateCurrentMigrationFile(end($files));
    }

    protected function getMigrationFilesAfterCurrentMigration(): array
    {
        $files = scandir($this->laravelMigrationsPath, SCANDIR_SORT_ASCENDING);

        $currentMigration = '';
        if (file_exists($this->laravelMigrationsPath . '/current_migration.txt')) {
             $currentMigration = file_get_contents("{$this->laravelMigrationsPath}/current_migration.txt");
        }

        return array_filter($files, function ($file) use ($currentMigration) {
            return str_ends_with($file, '.php') && strcasecmp($file, $currentMigration) > 0;
        });
    }

    protected function runDownMigrationsUntilCurrentMigration(array $files): void
    {
        if(!$this->repository->repositoryExists()) {
            return;
        }
        foreach (array_reverse($files) as $file) {
            $lastRunMigrations = $this->repository->getLast();
            $lastRunMigration = reset($lastRunMigrations);
            $migration = $this->resolvePath($this->laravelMigrationsPath . '/' . $file);

            if (!$lastRunMigration || $lastRunMigration->migration != substr($file, 0, -4)) {
                return;
            }

            $this->runMigration($migration, 'down');
            $this->repository->delete($lastRunMigration);
            $this->write(Info::class, "downed {$file}");
        }
    }

    protected function runUpMigrations(array $files): void
    {
        if(!$this->repository->repositoryExists()) {
            $this->repository->createRepository();
        }
        $nextBatchNumber = $this->repository->getNextBatchNumber();

        foreach ($files as $file) {
            $migrationPath = $this->laravelMigrationsPath . '/' . $file;
            $this->runMigration($this->resolvePath($migrationPath), 'up');
            $this->repository->log($this->getMigrationName($migrationPath), $nextBatchNumber);
            $this->write(Info::class, "upped {$file}");
        }
    }

    protected function filterOutMigrationQueries(array $queries): array
    {
        $queriesToIgnore = [
            'select * from information_schema.tables',
            'select "migration" from "migrations"',
            'select max("batch") as aggregate from "migrations"',
            'create table "migrations" (',
            'insert into "migrations',
            'select * from "migrations"',
        ];
        return array_filter($queries, function ($query) use ($queriesToIgnore) {
            foreach ($queriesToIgnore as $queryToIgnore) {
                if (str_starts_with($query['raw_query'], $queryToIgnore)) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * @throws \Exception
     */
    protected function writeMigrationsFile(string $outputFileName, array $queries): void
    {
        $dateString = Carbon::now()->format('Y_m_d_His');
        $filePath = "{$this->sqlMigrationsPath}/{$dateString}_{$outputFileName}";
        if(!file_exists($this->sqlMigrationsPath)) {
            mkdir($this->sqlMigrationsPath, recursive: true);
        }
        if(!file_exists($filePath)) {
            touch($filePath);
        }
        $migrationFile = fopen($filePath, 'w');
        if (!$migrationFile) {
            $this->write(Error::class, "Could not open file {$filePath}");
            throw new \Exception("Could not open file {$filePath}");
        }
        foreach ($queries as $query) {
            fwrite($migrationFile, $query['raw_query'] . ";\n\n");
        }
        fclose($migrationFile);

        $this->write(Info::class, "Written " . count($queries) . " queries to {$filePath}");
    }

    /**
     * @throws \Exception
     */
    protected function updateCurrentMigrationFile(string $latestMigrationFile): void
    {
        $currentMigrationFile = fopen("{$this->laravelMigrationsPath}/current_migration.txt", "w");
        if (!$currentMigrationFile) {
            $this->write(
                Error::class,
                "Could not open file {$this->laravelMigrationsPath}/current_migration.txt"
            );
            throw new \Exception("Could not open file {$this->laravelMigrationsPath}/current_migration.txt");
        }
        fwrite($currentMigrationFile, $latestMigrationFile);
        fclose($currentMigrationFile);
    }
}