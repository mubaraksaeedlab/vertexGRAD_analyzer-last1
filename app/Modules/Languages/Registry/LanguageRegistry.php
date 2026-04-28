<?php

namespace App\Modules\Languages\Registry;

use App\Modules\Languages\Contracts\LanguageAnalyzerInterface;
use App\Modules\Projects\Models\ProjectFile;

class LanguageRegistry
{
    /**
     * @var array<string, LanguageAnalyzerInterface>
     */
    protected array $analyzers = [];

    /**
     * تسجيل Analyzer جديد
     */
    public function register(LanguageAnalyzerInterface $analyzer): void
    {
        $this->analyzers[$analyzer->language()] = $analyzer;
    }

    /**
     * جلب جميع الـ analyzers
     */
    public function all(): array
    {
        return $this->analyzers;
    }

    /**
     * جلب Analyzer حسب اللغة
     */
    public function get(string $language): ?LanguageAnalyzerInterface
    {
        return $this->analyzers[$language] ?? null;
    }

    /**
     * البحث عن Analyzer حسب امتداد الملف
     */
    public function findByExtension(string $extension): ?LanguageAnalyzerInterface
    {
        $extension = strtolower(trim($extension));
        $extension = ltrim($extension, '.');

        foreach ($this->analyzers as $analyzer) {
            if (in_array($extension, $analyzer->supportedExtensions(), true)) {
                return $analyzer;
            }
        }

        return null;
    }

    /**
     * 🔥 أهم دالة: جلب كل analyzers المناسبة لمجموعة ملفات
     */
    public function analyzersForFiles(array $files): array
    {
        $matched = [];

        foreach ($files as $file) {
            if (!$file instanceof ProjectFile) {
                continue;
            }

            $extension = strtolower((string) $file->extension);

            $analyzer = $this->findByExtension($extension);

            if ($analyzer === null) {
                continue;
            }

            // منع التكرار (كل لغة مرة واحدة فقط)
            $matched[$analyzer->language()] = $analyzer;
        }

        return array_values($matched);
    }
}