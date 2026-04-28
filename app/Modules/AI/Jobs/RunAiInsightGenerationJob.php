<?php

namespace App\Modules\AI\Jobs;

use App\Modules\AI\Services\AiInsightOrchestrator;
use App\Modules\AI\Services\AiIssueInsightService;
use App\Modules\Analysis\Models\AnalysisRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunAiInsightGenerationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $analysisRunId
    ) {
    }

    public function handle(
        AiInsightOrchestrator $orchestrator,
        AiIssueInsightService $issueInsightService
    ): void {
        $analysisRun = AnalysisRun::with(['issues', 'project', 'score', 'reports'])
            ->find($this->analysisRunId);

        if (! $analysisRun) {
            return;
        }

        $orchestrator->generateForAnalysisRun($analysisRun);

        foreach ($analysisRun->issues as $issue) {
            $issueInsightService->generateForIssue($issue);
        }
    }
}