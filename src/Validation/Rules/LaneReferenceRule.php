<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Validation\Rules;

use MohamedZaki\LaravelProcessBuilder\Contracts\ValidationRule;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationError;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationResult;

final class LaneReferenceRule implements ValidationRule
{
    public function validate(ProcessDefinition $process): ValidationResult
    {
        if ($process->lanes === []) {
            return ValidationResult::valid();
        }

        $issues = [];

        foreach ($process->nodes as $node) {
            $laneId = $node->data['laneId'] ?? null;

            if ($laneId === null) {
                continue;
            }

            if (! is_string($laneId) || $process->laneById($laneId) === null) {
                $issues[] = ValidationError::warning(
                    'lane.unknown_reference',
                    "Node [{$node->id}] references a lane that does not exist.",
                    nodeId: $node->id,
                );
            }
        }

        return ValidationResult::fromIssues($issues);
    }
}
