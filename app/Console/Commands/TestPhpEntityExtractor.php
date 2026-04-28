<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Understanding\Services\PhpEntityExtractorService;
use App\Modules\Understanding\Services\UnderstandingPersistenceService;

class TestPhpEntityExtractor extends Command
{
    protected $signature = "understanding:test-php-extractor";
    protected $description = "Test PHP entity extractor and persist results";

    public function handle(): int
    {
        $extractor = app(PhpEntityExtractorService::class);
        $persistence = app(UnderstandingPersistenceService::class);

        $path = storage_path("app/understanding-tests/SampleTestController.php");

        if (!file_exists($path)) {
            $this->error("Test file not found: " . $path);
            return self::FAILURE;
        }

        $run = AnalysisRun::first();

        if (!$run) {
            $this->error("No analysis run found.");
            return self::FAILURE;
        }

        $entities = $extractor->extractFromFile($path);
        $persisted = $persistence->persistEntities($run->id, $entities);

        $this->info("Entities persisted successfully.");
        $this->line("Extracted count: " . count($entities));
        $this->line("Persisted count: " . count($persisted));

        return self::SUCCESS;
    }
}