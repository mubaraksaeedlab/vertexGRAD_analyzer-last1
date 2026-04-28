<?php

namespace App\Modules\Languages\Contracts;

use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;

interface LanguageAnalyzerInterface
{
    public function language(): string;

    public function supportedExtensions(): array;

    public function canHandle(ProjectFile $file): bool;

    public function analyze(Project $project, array $files): array;
}