<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Validation\Rules;

use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Contracts\ValidationRule;
use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationError;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationResult;
use MohamedZaki\LaravelProcessBuilder\Enums\NodeType;

final class RouteCollisionRule implements ValidationRule
{
    public function __construct(private readonly ProcessRepository $repository)
    {
    }

    public function validate(ProcessDefinition $process): ValidationResult
    {
        $issues = [];

        $routeNodes = array_values(array_filter(
            $process->nodes,
            static fn (ProcessNode $node): bool => $node->type === NodeType::Route,
        ));

        foreach ($routeNodes as $node) {
            $name = $node->data['name'] ?? null;

            if (! is_string($name) || $name === '') {
                continue;
            }

            foreach ($this->repository->all() as $other) {
                if ($other->id === $process->id) {
                    continue;
                }

                foreach ($other->nodes as $otherNode) {
                    if ($otherNode->type !== NodeType::Route) {
                        continue;
                    }

                    if (($otherNode->data['name'] ?? null) === $name) {
                        $issues[] = ValidationError::error(
                            'route.duplicate_name',
                            "The route name [{$name}] is already used by process [{$other->name}].",
                            nodeId: $node->id,
                            field: 'name',
                        );
                    }
                }
            }
        }

        return ValidationResult::fromIssues($issues);
    }
}
