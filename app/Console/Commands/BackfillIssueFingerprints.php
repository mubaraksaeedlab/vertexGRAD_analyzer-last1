<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Analysis\Models\Issue;
use App\Modules\Analysis\Services\IssueFingerprintService;

class BackfillIssueFingerprints extends Command
{
    protected $signature = 'issues:backfill-fingerprints';

    protected $description = 'Generate fingerprints for existing issues';

    public function handle()
    {
        $service = app(IssueFingerprintService::class);

        $count = 0;

        Issue::whereNull('fingerprint')->chunk(100, function ($issues) use ($service, &$count) {
            foreach ($issues as $issue) {
                $filePath = null;

                if (is_array($issue->metadata) && isset($issue->metadata['relative_path'])) {
                    $filePath = $issue->metadata['relative_path'];
                }

                $data = $service->make(
                    $issue->rule_code,
                    $issue->category,
                    $issue->severity,
                    $filePath,
                    $issue->snippet,
                    $issue->title
                );

                $issue->fingerprint = $data['fingerprint'];
                $issue->normalized_snippet = $data['normalized_snippet'];
                $issue->fingerprint_version = $data['fingerprint_version'];
                $issue->save();

                $count++;
            }
        });

        $this->info("Done. Updated {$count} issues.");
    }
}