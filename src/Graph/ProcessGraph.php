<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Graph;

use MohamedZaki\LaravelProcessBuilder\Domain\Edges\ProcessEdge;
use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;

final class ProcessGraph
{
    /** @var array<string, list<string>> */
    private readonly array $adjacency;

    public function __construct(private readonly ProcessDefinition $process)
    {
        $adjacency = [];

        foreach ($process->nodes as $node) {
            $adjacency[$node->id] = [];
        }

        foreach ($process->edges as $edge) {
            $adjacency[$edge->source][] = $edge->target;
        }

        $this->adjacency = $adjacency;
    }

    /**
     * @return list<string>
     */
    public function neighbors(string $nodeId): array
    {
        return $this->adjacency[$nodeId] ?? [];
    }

    /**
     * Nodes reachable from the given start node (inclusive), via a breadth-first walk.
     *
     * @return array<string, bool>
     */
    public function reachableFrom(string $startNodeId): array
    {
        $visited = [$startNodeId => true];
        $queue = [$startNodeId];

        while ($queue !== []) {
            $current = array_shift($queue);

            foreach ($this->neighbors($current) as $neighbor) {
                if (! isset($visited[$neighbor])) {
                    $visited[$neighbor] = true;
                    $queue[] = $neighbor;
                }
            }
        }

        return $visited;
    }

    /**
     * Detect a cycle reachable from the entry node using DFS; returns the cycle's node ids, or null if acyclic.
     *
     * @return list<string>|null
     */
    public function findCycleFromEntry(): ?array
    {
        if ($this->process->entryNodeId === null) {
            return null;
        }

        $visiting = [];
        $visited = [];
        $stack = [];

        $cycle = $this->dfsDetectCycle($this->process->entryNodeId, $visiting, $visited, $stack);

        return $cycle;
    }

    /**
     * @param  array<string, bool>  $visiting
     * @param  array<string, bool>  $visited
     * @param  list<string>  $stack
     * @return list<string>|null
     */
    private function dfsDetectCycle(string $nodeId, array &$visiting, array &$visited, array $stack): ?array
    {
        if (isset($visiting[$nodeId])) {
            $cycleStart = array_search($nodeId, $stack, strict: true);

            return array_slice($stack, $cycleStart === false ? 0 : $cycleStart);
        }

        if (isset($visited[$nodeId])) {
            return null;
        }

        $visiting[$nodeId] = true;
        $stack[] = $nodeId;

        foreach ($this->neighbors($nodeId) as $neighbor) {
            $cycle = $this->dfsDetectCycle($neighbor, $visiting, $visited, $stack);

            if ($cycle !== null) {
                return $cycle;
            }
        }

        unset($visiting[$nodeId]);
        $visited[$nodeId] = true;

        return null;
    }

    /**
     * @return list<ProcessNode>
     */
    public function orphanNodes(): array
    {
        if ($this->process->entryNodeId === null) {
            return [];
        }

        $reachable = $this->reachableFrom($this->process->entryNodeId);

        return array_values(array_filter(
            $this->process->nodes,
            static fn (ProcessNode $node): bool => ! isset($reachable[$node->id]),
        ));
    }

    /**
     * @return list<ProcessEdge>
     */
    public function incomingEdges(string $nodeId): array
    {
        return array_values(array_filter(
            $this->process->edges,
            static fn (ProcessEdge $edge): bool => $edge->target === $nodeId,
        ));
    }

    /**
     * @return list<ProcessEdge>
     */
    public function outgoingEdges(string $nodeId): array
    {
        return array_values(array_filter(
            $this->process->edges,
            static fn (ProcessEdge $edge): bool => $edge->source === $nodeId,
        ));
    }
}
