<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Validation\Rules;

use MohamedZaki\LaravelProcessBuilder\Contracts\ValidationRule;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationError;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationResult;

final class ParticipantReferenceRule implements ValidationRule
{
    public function validate(ProcessDefinition $process): ValidationResult
    {
        if ($process->participants === []) {
            return ValidationResult::fromIssues([
                ValidationError::error(
                    'participant.required',
                    'Create at least one participant and map it to a guard before designing or publishing this process.',
                ),
            ]);
        }

        $issues = [];
        foreach ($process->nodes as $node) {
            $participantId = $node->data['participantId'] ?? null;
            if (! is_string($participantId) || $process->participantById($participantId) === null) {
                $issues[] = ValidationError::error(
                    'participant.unassigned_node',
                    "Node [{$node->id}] must belong to an existing participant.",
                    nodeId: $node->id,
                );
            }
        }

        return ValidationResult::fromIssues($issues);
    }
}
