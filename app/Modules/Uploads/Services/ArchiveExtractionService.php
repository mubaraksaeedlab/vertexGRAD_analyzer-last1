<?php

namespace App\Modules\Uploads\Services;

use App\Modules\Projects\Models\Project;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class ArchiveExtractionService
{
    public function extract(Project $project, string $archivePath): string
    {
        $fullArchivePath = Storage::disk('local')->path($archivePath);

        if (!File::exists($fullArchivePath)) {
            throw new RuntimeException('Archive file not found: ' . $fullArchivePath);
        }

        $extractDirectory = storage_path('app/private/extracted/project_' . $project->id);

        if (File::exists($extractDirectory)) {
            File::deleteDirectory($extractDirectory);
        }

        File::ensureDirectoryExists($extractDirectory);

        $extension = strtolower(pathinfo($fullArchivePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'zip' => $this->extractZip($fullArchivePath, $extractDirectory),
            'rar' => $this->extractRar($fullArchivePath, $extractDirectory),
            default => throw new RuntimeException(
                "Unsupported archive type: {$extension}. Only ZIP and RAR are supported."
            ),
        };
    }

    protected function extractZip(string $fullArchivePath, string $extractDirectory): string
    {
        $zip = new ZipArchive();

        $opened = $zip->open($fullArchivePath);

        if ($opened !== true) {
            throw new RuntimeException('Unable to open ZIP archive.');
        }

        if (!$zip->extractTo($extractDirectory)) {
            $zip->close();
            throw new RuntimeException('Failed to extract ZIP archive.');
        }

        $zip->close();

        return $extractDirectory;
    }

    protected function extractRar(string $fullArchivePath, string $extractDirectory): string
    {
        if (extension_loaded('rar')) {
            return $this->extractRarWithPhpExtension($fullArchivePath, $extractDirectory);
        }

        $command = $this->findRarCommand();

        if (!$command) {
            throw new RuntimeException(
                'RAR extraction is not available. 7-Zip / UnRAR / WinRAR was not found on this server.'
            );
        }

        return $this->extractRarWithCommand($command, $fullArchivePath, $extractDirectory);
    }

    protected function extractRarWithPhpExtension(string $fullArchivePath, string $extractDirectory): string
    {
        $rar = \RarArchive::open($fullArchivePath);

        if (!$rar) {
            throw new RuntimeException('Unable to open RAR archive using PHP rar extension.');
        }

        $entries = $rar->getEntries();

        if ($entries === false) {
            $rar->close();
            throw new RuntimeException('Failed to read entries from the RAR archive.');
        }

        foreach ($entries as $entry) {
            /** @var \RarEntry $entry */
            if (!$entry->extract($extractDirectory)) {
                $rar->close();
                throw new RuntimeException('Failed to extract one or more files from the RAR archive.');
            }
        }

        $rar->close();

        return $extractDirectory;
    }

    protected function extractRarWithCommand(string $command, string $fullArchivePath, string $extractDirectory): string
    {
        $archive = escapeshellarg($fullArchivePath);
        $destination = escapeshellarg($extractDirectory);

        $lowerCommand = strtolower($command);

        if (str_contains($lowerCommand, '7z.exe') || str_contains($lowerCommand, '\7z')) {
            $execCommand = "\"{$command}\" x {$archive} -o{$destination} -y 2>&1";
        } else {
            $destinationWithSlash = escapeshellarg($extractDirectory . DIRECTORY_SEPARATOR);
            $execCommand = "\"{$command}\" x -o+ {$archive} {$destinationWithSlash} 2>&1";
        }

        exec($execCommand, $output, $exitCode);

        Log::info('Archive extraction command executed', [
            'command' => $command,
            'exec_command' => $execCommand,
            'archive' => $fullArchivePath,
            'destination' => $extractDirectory,
            'exit_code' => $exitCode,
            'output' => $output,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'Failed to extract RAR archive. Output: ' . implode("\n", $output)
            );
        }

        if (!$this->directoryHasFiles($extractDirectory)) {
            throw new RuntimeException('RAR archive extraction finished, but no files were extracted.');
        }

        return $extractDirectory;
    }

    protected function findRarCommand(): ?string
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $directCandidates = [
                'C:\\Program Files\\7-Zip\\7z.exe',
                'C:\\Program Files (x86)\\7-Zip\\7z.exe',
                'C:\\Program Files\\WinRAR\\UnRAR.exe',
                'C:\\Program Files\\WinRAR\\WinRAR.exe',
            ];

            foreach ($directCandidates as $candidate) {
                if (File::exists($candidate)) {
                    return $candidate;
                }
            }

            $pathCandidates = [
                '7z.exe',
                '7z',
                'UnRAR.exe',
                'unrar.exe',
                'WinRAR.exe',
                'winrar.exe',
            ];
        } else {
            $pathCandidates = [
                '7z',
                '7zz',
                'unrar',
                'rar',
            ];
        }

        foreach ($pathCandidates as $candidate) {
            $found = $this->commandPath($candidate);

            if ($found) {
                return $found;
            }
        }

        return null;
    }

    protected function commandPath(string $command): ?string
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $checkCommand = $isWindows
            ? "where {$command}"
            : "command -v {$command}";

        exec($checkCommand, $output, $exitCode);

        if ($exitCode !== 0 || empty($output[0])) {
            return null;
        }

        return trim($output[0]);
    }

    protected function directoryHasFiles(string $directory): bool
    {
        if (!File::exists($directory)) {
            return false;
        }

        $files = File::allFiles($directory);

        return count($files) > 0;
    }
}