<?php

namespace App\Modules\Understanding\DTOs;

class ExtractedEntityData
{
    public function __construct(
        public string $entityType,
        public string $name,
        public ?string $qualifiedName = null,
        public ?int $startLine = null,
        public ?int $endLine = null,
        public array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            "entity_type" => $this->entityType,
            "name" => $this->name,
            "qualified_name" => $this->qualifiedName,
            "start_line" => $this->startLine,
            "end_line" => $this->endLine,
            "metadata" => $this->metadata,
        ];
    }
}