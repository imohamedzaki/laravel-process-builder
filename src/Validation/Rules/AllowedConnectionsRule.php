<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Validation\Rules;

use MohamedZaki\LaravelProcessBuilder\Contracts\ValidationRule;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationError;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationResult;
use MohamedZaki\LaravelProcessBuilder\Graph\ConnectionMap;

final class AllowedConnectionsRule implements ValidationRule
{
    public function validate(ProcessDefinition $process): ValidationResult
    {
        $issues = [];

        foreach ($process->edges as $edge) {
            $source = $process->nodeById($edge->source);
            $target = $process->nodeById($edge->target);

            if ($source === null || $target === null) {
                // Missing-node references are reported by ProcessDefinition hydration itself.
                continue;
            }

            if (! ConnectionMap::isAllowed($source->type, $target->type, $edge->sourceHandle)) {
                $issues[] = ValidationError::error(
                    'graph.invalid_connection',
                    "Edge [{$edge->id}] connects [{$source->type->value}] to [{$target->type->value}], which is not an allowed connection.",
                    nodeId: $edge->source,
                );
            }
        }

        return ValidationResult::fromIssues($issues);
    }
}
