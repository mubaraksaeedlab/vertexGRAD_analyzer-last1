<?php

namespace App\Modules\Understanding\Services;

use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Analysis\Models\AnalysisRunFile;
use App\Modules\Projects\Models\ProjectFile;
use App\Modules\Understanding\Models\CodeEntity;
use App\Modules\Understanding\Models\CodeRelationship;
use Illuminate\Support\Facades\DB;

class RunUnderstandingService
{
    public function __construct(
        protected PhpEntityExtractorService $extractor,
        protected UnderstandingPersistenceService $persistence,
    ) {
    }

    public function processRun(int $runId): array
    {
        return DB::transaction(function () use ($runId) {
            $run = AnalysisRun::query()->findOrFail($runId);

            $this->clearRunUnderstandingData($run->id);

            $runFiles = AnalysisRunFile::query()
                ->where("analysis_run_id", $run->id)
                ->get();

            $processedFiles = 0;
            $processedEntities = 0;
            $skippedFiles = 0;

            foreach ($runFiles as $runFile) {
                $projectFile = ProjectFile::query()->find($runFile->project_file_id);

                if (!$projectFile) {
                    $skippedFiles++;
                    continue;
                }

                if (($projectFile->extension ?? null) !== "php") {
                    $skippedFiles++;
                    continue;
                }

                if (!$projectFile->is_readable || empty($projectFile->path) || !is_file($projectFile->path)) {
                    $skippedFiles++;
                    continue;
                }

                $entities = $this->extractor->extractFromFile($projectFile->path);

                $this->persistence->persistEntities(
                    analysisRunId: $run->id,
                    entities: $entities,
                    fileId: $runFile->id,
                );

                $processedFiles++;
                $processedEntities += count($entities);
            }

            return [
                "run_id" => $run->id,
                "processed_files" => $processedFiles,
                "processed_entities" => $processedEntities,
                "skipped_files" => $skippedFiles,
            ];
        });
    }

    protected function clearRunUnderstandingData(int $runId): void
    {
        CodeRelationship::query()
            ->where("analysis_run_id", $runId)
            ->delete();

        CodeEntity::query()
            ->where("analysis_run_id", $runId)
            ->delete();
    }
}