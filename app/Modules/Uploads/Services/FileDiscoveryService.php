<?php

namespace App\Modules\Uploads\Services;

use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FileDiscoveryService
{
    protected array $ignoredDirectories = [
        '.git',
        '.idea',
        '.vscode',
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
        '__MACOSX',
    ];

    protected array $ignoredFiles = [
        '.DS_Store',
        'Thumbs.db',
    ];

    protected int $insertChunkSize = 300;

    public function discoverAndStore(Project $project, string $extractedPath): int
    {
        if (!File::exists($extractedPath)) {
            return 0;
        }

        $now = now();
        $storedCount = 0;
        $rows = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractedPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            $fullPath = $file->getPathname();

            if ($this->shouldIgnore($fullPath, $extractedPath)) {
                continue;
            }

            $relativePath = str_replace('\\', '/', $this->relativePath($fullPath, $extractedPath));
            $fileName = $file->getFilename();
            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            $extension = strtolower($file->getExtension());

            $isReadable = is_readable($fullPath);
            $isHidden = str_starts_with($fileName, '.');
            $isVendor = $this->isVendorFile($relativePath);
            $isTest = $this->isTestFile($relativePath, $fileName);
            $isConfig = $this->isConfigFile($relativePath, $extension);

            $size = $file->getSize() ?: 0;

            $language = $this->detectLanguageFromPath($relativePath, $extension);
            $isSource = $this->isSourceCodeFile($relativePath, $extension, $language);
            $isBinary = $this->isBinaryFileByExtension($extension, $isSource);

            $category = $this->detectCategory(
                isSource: $isSource,
                isConfig: $isConfig,
                isTest: $isTest,
                isVendor: $isVendor,
                isBinary: $isBinary
            );

            // تحسين مهم:
            // لا نحسب عدد الأسطر إلا للملفات النصية المصدرية الصغيرة نسبيًا
            $lineCount = null;
            if ($isReadable && !$isBinary && $isSource && $size <= 1024 * 1024) {
                $lineCount = $this->countLinesFast($fullPath);
            }

            // تحسين مهم:
            // لا نحسب hash لكل ملف لأنه مكلف جدًا
            $hash = null;

            $rows[] = [
                'project_id' => $project->id,
                'disk' => 'local',
                'path' => $fullPath,
                'relative_path' => $relativePath,
                'file_name' => $fileName,
                'base_name' => $baseName,
                'extension' => $extension ?: null,
                'mime_type' => null,
                'language' => $language,
                'category' => $category,
                'size' => $size,
                'line_count' => $isBinary ? 0 : ($lineCount ?? 0),
                'hash' => $hash,
                'is_source' => $isSource,
                'is_config' => $isConfig,
                'is_test' => $isTest,
                'is_vendor' => $isVendor,
                'is_binary' => $isBinary,
                'is_hidden' => $isHidden,
                'is_readable' => $isReadable,
                'metadata' => json_encode([
                    'full_path' => $fullPath,
                    'directory' => dirname($relativePath),
                ], JSON_UNESCAPED_UNICODE),
                'discovered_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) >= $this->insertChunkSize) {
                ProjectFile::insert($rows);
                $storedCount += count($rows);
                $rows = [];
            }
        }

        if (!empty($rows)) {
            ProjectFile::insert($rows);
            $storedCount += count($rows);
        }

        return $storedCount;
    }

    protected function relativePath(string $fullPath, string $basePath): string
    {
        $normalizedFullPath = str_replace('\\', '/', $fullPath);
        $normalizedBasePath = rtrim(str_replace('\\', '/', $basePath), '/');

        return ltrim(str_replace($normalizedBasePath, '', $normalizedFullPath), '/');
    }

    protected function shouldIgnore(string $fullPath, string $basePath): bool
    {
        $relativePath = $this->relativePath($fullPath, $basePath);

        foreach ($this->ignoredDirectories as $ignoredDirectory) {
            $ignoredDirectory = trim(str_replace('\\', '/', $ignoredDirectory), '/');

            if (
                str_contains($relativePath, '/' . $ignoredDirectory . '/') ||
                str_starts_with($relativePath, $ignoredDirectory . '/') ||
                $relativePath === $ignoredDirectory
            ) {
                return true;
            }
        }

        foreach ($this->ignoredFiles as $ignoredFile) {
            if (basename($relativePath) === $ignoredFile) {
                return true;
            }
        }

        return false;
    }

    protected function isSourceCodeFile(string $relativePath, ?string $extension, ?string $language): bool
    {
        if ($language !== null) {
            return true;
        }

        $extension = strtolower((string) $extension);
        $path = strtolower($relativePath);

        if (str_ends_with($path, '.blade.php')) {
            return true;
        }

        $sourceExtensions = [
            'php', 'js', 'mjs', 'cjs', 'ts', 'tsx', 'jsx',
            'py', 'java', 'cs', 'c', 'cpp', 'cc', 'cxx', 'h', 'hpp',
            'go', 'dart', 'rb', 'rs', 'swift', 'kt', 'kts',
            'sql', 'html', 'htm', 'css', 'scss', 'sass', 'less',
            'json', 'xml', 'yaml', 'yml', 'sh', 'bat', 'md',
        ];

        return in_array($extension, $sourceExtensions, true);
    }

    protected function isConfigFile(string $relativePath, ?string $extension): bool
    {
        $relativePath = strtolower($relativePath);
        $extension = strtolower((string) $extension);

        return str_contains($relativePath, 'config/')
            || in_array($extension, ['env', 'ini', 'yaml', 'yml', 'json', 'xml', 'toml'], true)
            || str_ends_with($relativePath, '.env')
            || str_contains($relativePath, 'docker')
            || str_contains($relativePath, 'compose');
    }

    protected function isTestFile(string $relativePath, string $fileName): bool
    {
        $relativePath = strtolower($relativePath);
        $fileName = strtolower($fileName);

        return str_contains($relativePath, '/tests/')
            || str_contains($relativePath, '/test/')
            || str_contains($fileName, 'test')
            || str_contains($fileName, 'spec');
    }

    protected function isVendorFile(string $relativePath): bool
    {
        $relativePath = strtolower($relativePath);

        return str_contains($relativePath, 'vendor/')
            || str_contains($relativePath, 'node_modules/');
    }

    protected function isBinaryFileByExtension(?string $extension, bool $isSource): bool
    {
        if ($isSource) {
            return false;
        }

        $extension = strtolower((string) $extension);

        $binaryExtensions = [
            'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'ico',
            'pdf', 'zip', 'rar', '7z',
            'exe', 'dll', 'so',
            'mp3', 'mp4', 'avi', 'mov', 'wav',
            'ttf', 'woff', 'woff2',
            'psd', 'ai',
        ];

        return in_array($extension, $binaryExtensions, true);
    }

    protected function countLinesFast(string $fullPath): int
    {
        $handle = @fopen($fullPath, 'rb');

        if (!$handle) {
            return 0;
        }

        $lines = 0;

        while (!feof($handle)) {
            $chunk = fread($handle, 8192);

            if ($chunk === false) {
                break;
            }

            $lines += substr_count($chunk, "\n");
        }

        fclose($handle);

        return $lines > 0 ? $lines + 1 : 1;
    }

    protected function detectLanguageFromPath(string $relativePath, ?string $extension = null): ?string
    {
        $path = strtolower(str_replace('\\', '/', $relativePath));
        $extension = strtolower($extension ?: pathinfo($path, PATHINFO_EXTENSION));

        if (str_ends_with($path, '.blade.php')) {
            return 'php';
        }

        return match ($extension) {
            'php' => 'php',
            'js', 'mjs', 'cjs' => 'javascript',
            'ts' => 'typescript',
            'tsx' => 'tsx',
            'jsx' => 'jsx',
            'py' => 'python',
            'java' => 'java',
            'cs' => 'csharp',
            'go' => 'go',
            'rb' => 'ruby',
            'rs' => 'rust',
            'cpp', 'cc', 'cxx' => 'cpp',
            'c' => 'c',
            'h', 'hpp' => 'cpp',
            'swift' => 'swift',
            'kt', 'kts' => 'kotlin',
            'dart' => 'dart',
            'vue' => 'vue',
            'css', 'scss', 'sass', 'less' => 'css',
            'html', 'htm' => 'html',
            'xml' => 'xml',
            'json' => 'json',
            'yml', 'yaml' => 'yaml',
            'sql' => 'sql',
            'md' => 'markdown',
            'sh', 'bash' => 'shell',
            default => null,
        };
    }

    protected function detectCategory(
        bool $isSource,
        bool $isConfig,
        bool $isTest,
        bool $isVendor,
        bool $isBinary
    ): string {
        return match (true) {
            $isVendor => 'vendor',
            $isTest => 'test',
            $isConfig => 'config',
            $isBinary => 'binary',
            $isSource => 'source',
            default => 'other',
        };
    }
}