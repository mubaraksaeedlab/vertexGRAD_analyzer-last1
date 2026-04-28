<?php

namespace App\Modules\Analysis\Services;

use App\Modules\Understanding\Models\CodeEntity;
use App\Modules\Understanding\Models\CodeRelationship;
use App\Modules\Projects\Models\ProjectFile;
use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Analysis\Models\Issue;

class UnderstandingBasedIssueService
{
    public function generateForRun(int $runId): array
    {
        $run = AnalysisRun::query()->findOrFail($runId);

        $this->clearUnderstandingIssues($runId);

        $created = 0;

        $controllers = CodeEntity::query()
            ->where("analysis_run_id", $runId)
            ->where("entity_type", "controller")
            ->get();

        foreach ($controllers as $controller) {
            $methodsCount = CodeRelationship::query()
                ->where("analysis_run_id", $runId)
                ->where("source_entity_id", $controller->id)
                ->where("relationship_type", "contains")
                ->count();

            if ($methodsCount < 10) {
                continue;
            }

            $severity = $methodsCount >= 15 ? "high" : "medium";

            $projectFileId = null;

            if ($controller->file_id) {
                $runFile = \App\Modules\Analysis\Models\AnalysisRunFile::query()->find($controller->file_id);
                $projectFileId = $runFile?->project_file_id;
            }

            Issue::create([
                "project_id" => $run->project_id,
                "analysis_run_id" => $run->id,
                "project_file_id" => $projectFileId,
                "rule_code" => "controller_too_large",
                "category" => "architecture",
                "severity" => $severity,
                "language" => "php",
                "title" => "Controller contains too many methods",
                "description" => "The controller {$controller->name} contains {$methodsCount} methods, which may indicate excessive responsibility and reduced maintainability.",
                "recommendation" => "Consider splitting this controller into smaller controllers or moving business logic into dedicated service classes.",
                "line_start" => $controller->start_line,
                "line_end" => $controller->end_line,
                "column_start" => null,
                "column_end" => null,
                "snippet" => $controller->qualified_name,
                "confidence" => 0.90,
                "is_resolved" => false,
                "resolved_at" => null,
                "metadata" => json_encode([
                    "source" => "understanding_based_issue_service",
                    "entity_id" => $controller->id,
                    "entity_type" => $controller->entity_type,
                    "entity_name" => $controller->name,
                    "qualified_name" => $controller->qualified_name,
                    "methods_count" => $methodsCount,
                    "threshold" => 10,
                ], JSON_UNESCAPED_UNICODE),
                "fingerprint" => hash("sha256", "controller_too_large|{$run->id}|{$controller->qualified_name}|{$methodsCount}"),
                "normalized_snippet" => strtolower((string) $controller->qualified_name),
                "fingerprint_version" => "v1",
            ]);

            $created++;
        }

        return [
            "run_id" => $runId,
            "issues_created" => $created,
        ];
    }

    protected function clearUnderstandingIssues(int $runId): void
    {
        Issue::query()
            ->where("analysis_run_id", $runId)
            ->where("rule_code", "controller_too_large")
            ->delete();
    }
}