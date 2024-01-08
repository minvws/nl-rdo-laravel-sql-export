<?php

declare(strict_types=1);

namespace MinVWS\SqlExporter\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use MinVWS\SqlExporter\Services\ExportMigrationService;

class ExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sql-export {outputMigrationName} {--laravelMigrationsPath=} {--sqlMigrationsPath=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sql output for the laravel migrations';

    /**
     * @param ExportMigrationService $exportMigrationService
     *
     * @throws Exception
     */
    public function handle(ExportMigrationService $exportMigrationService): void
    {
        $this->setCommandArguments($exportMigrationService);

        $exportMigrationService->setOutput($this->output);
        $exportMigrationService->migrateToOutputFile();
    }

    private function setCommandArguments(ExportMigrationService $exportMigrationService): void
    {
        $outputMigrationNameArgument = $this->argument('outputMigrationName');
        assert(
            !empty($outputMigrationNameArgument) && is_string($outputMigrationNameArgument),
            "The outputMigrationName should be set as a command line argument"
        );
        $exportMigrationService->setOutputMigrationName($outputMigrationNameArgument);
        $laravelMigrationsPathArgument = $this->option('laravelMigrationsPath');
        if (is_string($laravelMigrationsPathArgument)) {
            $exportMigrationService->setLaravelMigrationsPath($laravelMigrationsPathArgument);
        }
        $sqlMigrationsPathArgument = $this->option('sqlMigrationsPath');
        if (is_string($sqlMigrationsPathArgument)) {
            $exportMigrationService->setSqlMigrationsPath($sqlMigrationsPathArgument);
        }
    }
}
