<?php

declare(strict_types=1);

namespace MinVWS\SqlExporter\Services;

use Illuminate\Console\View\Components\Error;
use Illuminate\Console\View\Components\Info;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class ExportMigrationService extends Migrator
{
    private string|null $outputMigrationName = null;

    private string $laravelMigrationsPath;

    private string $sqlMigrationsPath;

    /**
     * @var string[] $sqlExcludedQueries
     */
    private array $sqlExcludedQueries;

    /**
     * @param string[] $sqlExcludedQueries
     */
    public function __construct(
        MigrationRepositoryInterface $repository,
        ConnectionResolverInterface $resolver,
        Filesystem $files,
        Dispatcher $dispatcher,
        string $laravelMigrationsPath,
        string $sqlMigrationsPath,
        array $sqlExcludedQueries = [],
    ) {
        parent::__construct($repository, $resolver, $files, $dispatcher);
        $this->laravelMigrationsPath = $laravelMigrationsPath;
        $this->sqlMigrationsPath = $sqlMigrationsPath;
        $this->sqlExcludedQueries = $sqlExcludedQueries;
    }

    /**
     * @throws \Exception
     */
    public function migrateToOutputFile(): void
    {
        $files = $this->getMigrationFilesAfterCurrentMigration();
        $this->runDownMigrationsUntilCurrentMigration($files);

        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $this->runUpMigrations($files);
        $queries = $this->filterOutMigrationQueries(DB::getRawQueryLog());

        if (empty($queries)) {
            $this->write(Error::class, "No queries to write to SQL migration file.");
            return;
        }

        if (empty($this->outputMigrationName)) {
            $this->write(Error::class, "The outputMigrationName should be set as a command line argument.");
            return;
        }
        $this->writeMigrationsFile($this->outputMigrationName, $queries);

        if (!empty($files)) {
            $this->updateCurrentMigrationFile(end($files));
        }
    }

    public function setOutputMigrationName(string $outputMigrationName): void
    {
        $this->outputMigrationName = $outputMigrationName;
    }

    public function setLaravelMigrationsPath(string $laravelMigrationsPath): void
    {
        $this->laravelMigrationsPath = $laravelMigrationsPath;
    }

    public function setSqlMigrationsPath(string $sqlMigrationsPath): void
    {
        $this->sqlMigrationsPath = $sqlMigrationsPath;
    }

    /**
     * @return string[]
     */
    protected function getMigrationFilesAfterCurrentMigration(): array
    {
        $files = scandir($this->laravelMigrationsPath, SCANDIR_SORT_ASCENDING);
        if (!is_array($files)) {
            return [];
        }
        $currentMigration = '';
        if (file_exists($this->laravelMigrationsPath . '/current_migration.txt')) {
            $currentMigration = file_get_contents("{$this->laravelMigrationsPath}/current_migration.txt");
            assert(is_string($currentMigration));
        }
        return array_filter($files, function ($file) use ($currentMigration) {
            return str_ends_with($file, '.php') && strcasecmp($file, $currentMigration) > 0;
        });
    }

    /**
     * @param string[] $files
     */
    protected function runDownMigrationsUntilCurrentMigration(array $files): void
    {
        if (!$this->repository->repositoryExists()) {
            return;
        }
        foreach (array_reverse($files) as $file) {
            $lastRunMigrations = $this->repository->getLast();
            $lastRunMigration = reset($lastRunMigrations);
            $migration = $this->resolvePath($this->laravelMigrationsPath . '/' . $file);
            if (!$lastRunMigration || $lastRunMigration->migration != substr($file, 0, -4)) {
                continue;
            }
            $this->runMigration($migration, 'down');
            $this->repository->delete($lastRunMigration);
            $this->write(Info::class, "downed {$file}");
        }
    }

    /**
     * @param string[] $files
     */
    protected function runUpMigrations(array $files): void
    {
        if (!$this->repository->repositoryExists()) {
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

    /**
     * @param array{raw_query:string}[] $queries
     * @return array{raw_query:string}[]
     */
    protected function filterOutMigrationQueries(array $queries): array
    {
        $queriesToIgnore = $this->getQueriesToIgnore();
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
     * @param string $outputFileName
     * @param array{raw_query:string}[] $queries
     *
     * @throws \Exception
     */
    protected function writeMigrationsFile(string $outputFileName, array $queries): void
    {
        $dateString = Date::now()->format('Y_m_d_His');
        $filePath = "{$this->sqlMigrationsPath}/{$dateString}_{$outputFileName}.sql";
        if (!file_exists($this->sqlMigrationsPath)) {
            mkdir($this->sqlMigrationsPath, recursive: true);
        }
        if (!file_exists($filePath)) {
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

    /**
     * Get the queries that should be ignored during the export.
     *
     * @return string[]
     */
    public function getQueriesToIgnore(): array
    {
        return $this->sqlExcludedQueries;
    }
}
