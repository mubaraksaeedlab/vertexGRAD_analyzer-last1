<?php

namespace App\Modules\Understanding\Services;

use App\Modules\Understanding\Models\CodeEntity;
use App\Modules\Understanding\Models\CodeRelationship;

class UnderstandingPersistenceService
{
    public function persistEntities(int $analysisRunId, array $entities, ?int $fileId = null): array
    {
        $persisted = [];
        $parentEntity = null;

        foreach ($entities as $item) {
            $entity = CodeEntity::create([
                "analysis_run_id" => $analysisRunId,
                "file_id" => $fileId,
                "entity_type" => $item->entityType,
                "name" => $item->name,
                "qualified_name" => $item->qualifiedName,
                "start_line" => $item->startLine,
                "end_line" => $item->endLine,
                "metadata" => array_merge($item->metadata, [
                    "source" => "understanding_persistence_service"
                ]),
            ]);

            $persisted[] = $entity;

            if (
                in_array($item->entityType, ["class", "controller", "model", "service"], true)
                && $parentEntity === null
            ) {
                $parentEntity = $entity;
            }
        }

        if ($parentEntity) {
            foreach ($persisted as $entity) {
                if ($entity->id === $parentEntity->id) {
                    continue;
                }

                if ($entity->entity_type === "method") {
                    CodeRelationship::create([
                        "analysis_run_id" => $analysisRunId,
                        "source_entity_id" => $parentEntity->id,
                        "target_entity_id" => $entity->id,
                        "relationship_type" => "contains",
                        "metadata" => [
                            "source" => "understanding_persistence_service"
                        ],
                    ]);
                }
            }
        }

        return $persisted;
    }
}