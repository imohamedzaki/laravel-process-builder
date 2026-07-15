<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Validation\Rules;

use MohamedZaki\LaravelProcessBuilder\Contracts\ValidationRule;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationError;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationResult;
use MohamedZaki\LaravelProcessBuilder\Enums\NodeType;
use MohamedZaki\LaravelProcessBuilder\Graph\ProcessGraph;

final class GraphStructureRule implements ValidationRule
{
    public function validate(ProcessDefinition $process): ValidationResult
    {
        $issues = [];

        if ($process->nodes === []) {
            return ValidationResult::fromIssues([
                ValidationError::error('graph.empty', 'The process has no nodes.'),
            ]);
        }

        if ($process->entryNodeId === null) {
            $issues[] = ValidationError::error('graph.no_entry_node', 'The process must have an entry node.');
        } elseif ($process->nodeById($process->entryNodeId) === null) {
            $issues[] = ValidationError::error(
                'graph.entry_node_missing',
                "The entry node [{$process->entryNodeId}] does not exist.",
            );
        }

        $routeNodes = array_values(array_filter(
            $process->nodes,
            static fn ($node): bool => $node->type === NodeType::Route,
        ));

        if (count($routeNodes) > 1) {
            $issues[] = ValidationError::error(
                'graph.multiple_route_entries',
                'A route process may have only one route entry node.',
            );
        }

        $terminalTypes = [NodeType::Response, NodeType::End, NodeType::Success, NodeType::Failure];
        $hasTerminal = array_filter(
            $process->nodes,
            static fn ($node): bool => in_array($node->type, $terminalTypes, strict: true),
        ) !== [];

        if (! $hasTerminal) {
            $issues[] = ValidationError::warning(
                'graph.no_terminal_response',
                'The process has no terminal response node.',
            );
        }

        if ($process->entryNodeId !== null && $process->nodeById($process->entryNodeId) !== null) {
            $graph = new ProcessGraph($process);

            foreach ($graph->orphanNodes() as $orphan) {
                $issues[] = ValidationError::warning(
                    'graph.orphan_node',
                    "Node [{$orphan->id}] is not reachable from the entry node.",
                    nodeId: $orphan->id,
                );
            }

            $cycle = $graph->findCycleFromEntry();

            if ($cycle !== null) {
                $issues[] = ValidationError::error(
                    'graph.cycle_detected',
                    'The process graph contains a cycle: '.implode(' -> ', $cycle),
                );
            }
        }

        return ValidationResult::fromIssues($issues);
    }
}
