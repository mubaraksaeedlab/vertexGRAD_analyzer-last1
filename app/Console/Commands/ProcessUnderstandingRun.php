<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Understanding\Services\RunUnderstandingService;

class ProcessUnderstandingRun extends Command
{
    protected $signature = "understanding:process-run {runId}";
    protected $description = "Process understanding extraction for a real analysis run";

    public function handle(): int
    {
        $runId = (int) $this->argument("runId");

        $service = app(RunUnderstandingService::class);
        $result = $service->processRun($runId);

        $this->info("Run understanding completed successfully.");
        $this->line("Run ID: " . $result["run_id"]);
        $this->line("Processed files: " . $result["processed_files"]);
        $this->line("Processed entities: " . $result["processed_entities"]);
        $this->line("Skipped files: " . $result["skipped_files"]);

        return self::SUCCESS;
    }
}