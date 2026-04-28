<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Analysis\Services\UnderstandingBasedIssueService;

class RunUnderstandingRules extends Command
{
    protected $signature = "understanding:run-rules {runId}";
    protected $description = "Generate issues from understanding layer";

    public function handle(): int
    {
        $runId = (int) $this->argument("runId");

        $service = app(UnderstandingBasedIssueService::class);
        $result = $service->generateForRun($runId);

        $this->info("Understanding rules executed successfully.");
        $this->line("Run ID: " . $result["run_id"]);
        $this->line("Issues created: " . $result["issues_created"]);

        return self::SUCCESS;
    }
}