<?php

namespace App\Modules\Understanding\Services;

use App\Modules\Understanding\DTOs\ExtractedEntityData;
use App\Modules\Understanding\Enums\EntityType;

class PhpEntityExtractorService
{
    public function extractFromFile(string $absolutePath): array
    {
        if (!is_file($absolutePath)) {
            throw new \InvalidArgumentException("PHP file not found: {$absolutePath}");
        }

        $content = file_get_contents($absolutePath);

        if ($content === false) {
            throw new \RuntimeException("Unable to read PHP file: {$absolutePath}");
        }

        return $this->extractFromString($content);
    }

    public function extractFromString(string $content): array
    {
        $entities = [];

        $namespace = $this->extractNamespace($content);
        $class = $this->extractClass($content);

        if ($class !== null) {
            $entityType = $this->detectClassEntityType($class["name"], $namespace);
            $qualifiedName = $namespace ? $namespace . "\\" . $class["name"] : $class["name"];

            $entities[] = new ExtractedEntityData(
                entityType: $entityType,
                name: $class["name"],
                qualifiedName: $qualifiedName,
                startLine: $class["start_line"],
                endLine: null,
                metadata: [
                    "language" => "php",
                    "kind" => $entityType,
                    "namespace" => $namespace,
                ],
            );
        }

        foreach ($this->extractMethods($content, $namespace, $class["name"] ?? null) as $method) {
            $entities[] = $method;
        }

        return $entities;
    }

    protected function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function extractClass(string $content): ?array
    {
        if (preg_match('/^\s*class\s+([A-Za-z_][A-Za-z0-9_]*)/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $name = $matches[1][0];
            $offset = $matches[1][1];
            $startLine = substr_count(substr($content, 0, $offset), "\n") + 1;

            return [
                "name" => $name,
                "start_line" => $startLine,
            ];
        }

        return null;
    }

    protected function extractMethods(string $content, ?string $namespace, ?string $className): array
    {
        $entities = [];

        if (!preg_match_all('/^\s*(public|protected|private)?\s*function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $entities;
        }

        foreach ($matches[2] as $match) {
            $methodName = $match[0];
            $offset = $match[1];
            $startLine = substr_count(substr($content, 0, $offset), "\n") + 1;

            $qualifiedName = $methodName;

            if ($className) {
                $base = $namespace ? $namespace . "\\" . $className : $className;
                $qualifiedName = $base . "::" . $methodName;
            }

            $entities[] = new ExtractedEntityData(
                entityType: EntityType::METHOD->value,
                name: $methodName,
                qualifiedName: $qualifiedName,
                startLine: $startLine,
                endLine: null,
                metadata: [
                    "language" => "php",
                    "kind" => "method",
                ],
            );
        }

        return $entities;
    }

    protected function detectClassEntityType(string $className, ?string $namespace): string
    {
        if (str_ends_with($className, "Controller")) {
            return EntityType::CONTROLLER->value;
        }

        if (str_ends_with($className, "Service")) {
            return EntityType::SERVICE->value;
        }

        if (
            str_ends_with($className, "Model") ||
            ($namespace && str_contains($namespace, "\\Models"))
        ) {
            return EntityType::MODEL->value;
        }

        return EntityType::CLASS_TYPE->value;
    }
}