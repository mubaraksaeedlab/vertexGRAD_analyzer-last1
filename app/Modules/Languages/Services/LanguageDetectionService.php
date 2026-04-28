<?php

namespace App\Modules\Languages\Services;

use App\Modules\Languages\Registry\LanguageRegistry;
use App\Modules\Projects\Models\ProjectFile;

class LanguageDetectionService
{
    public function __construct(
        protected LanguageRegistry $registry
    ) {
    }

    /**
     * Backward-compatible alias.
     */
    public function detect(ProjectFile $file): ?string
    {
        return $this->detectFileLanguage($file);
    }

    /**
     * Detect the language of a single file.
     */
    public function detectFileLanguage(ProjectFile $file): ?string
    {
        $extension = $this->normalizeExtension($file->extension ?? null);

        if (!$extension) {
            return null;
        }

        $analyzer = $this->registry->findByExtension($extension);

        return $analyzer?->language();
    }

    /**
     * Detect all languages used in a project and count files per language.
     *
     * Example return:
     * [
     *     'php' => 12,
     *     'javascript' => 5,
     *     'python' => 2,
     * ]
     */
    public function detectProjectLanguages(iterable $files): array
    {
        $languages = [];

        foreach ($files as $file) {
            if (!$file instanceof ProjectFile) {
                continue;
            }

            $language = $this->detectFileLanguage($file);

            if (!$language) {
                continue;
            }

            if (!isset($languages[$language])) {
                $languages[$language] = 0;
            }

            $languages[$language]++;
        }

        arsort($languages);

        return $languages;
    }

    /**
     * Detect the primary language of a project.
     * Returns the language with the highest file count.
     */
    public function detectPrimaryLanguage(iterable $files): ?string
    {
        $languages = $this->detectProjectLanguages($files);

        if (empty($languages)) {
            return null;
        }

        return array_key_first($languages);
    }

    /**
     * Group files by detected language.
     *
     * Example return:
     * [
     *     'php' => [ProjectFile, ProjectFile],
     *     'javascript' => [ProjectFile],
     * ]
     */
    public function groupFilesByLanguage(iterable $files): array
    {
        $grouped = [];

        foreach ($files as $file) {
            if (!$file instanceof ProjectFile) {
                continue;
            }

            $language = $this->detectFileLanguage($file);

            if (!$language) {
                continue;
            }

            if (!isset($grouped[$language])) {
                $grouped[$language] = [];
            }

            $grouped[$language][] = $file;
        }

        return $grouped;
    }

    /**
     * Normalize file extension for consistent analyzer lookup.
     */
    protected function normalizeExtension(?string $extension): ?string
    {
        if ($extension === null) {
            return null;
        }

        $extension = strtolower(trim($extension));

        if ($extension === '') {
            return null;
        }

        return ltrim($extension, '.');
    }
}