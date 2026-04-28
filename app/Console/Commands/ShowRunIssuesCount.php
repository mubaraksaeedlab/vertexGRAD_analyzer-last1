<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Analysis\Models\Issue;

class ShowRunIssuesCount extends Command
{
    protected $signature = 'runs:issues-count {project_id}';

    protected $description = 'Show issues count for each run of a project';

    public function handle()
    {
        $projectId = $this->argument('project_id');

        $runs = AnalysisRun::where('project_id', $projectId)
            ->orderBy('id')
            ->get();

        foreach ($runs as $run) {
            $count = Issue::where('analysis_run_id', $run->id)->count();

            $this->info("Run {$run->id} => {$count} issues");
        }
    }
}