<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Domain\Processes;

use MohamedZaki\LaravelProcessBuilder\Domain\Edges\ProcessEdge;
use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;
use MohamedZaki\LaravelProcessBuilder\Enums\ProcessStatus;
use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidProcessDefinitionException;

final class ProcessDefinition
{
    public const SCHEMA_VERSION = '1.0';

    /**
     * @param  list<ProcessNode>  $nodes
     * @param  list<ProcessEdge>  $edges
     */
    public function __construct(
        public readonly string $schemaVersion,
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly int $version,
        public readonly ProcessStatus $status,
        public readonly ?string $entryNodeId,
        public readonly array $nodes,
        public readonly array $edges,
        public readonly ProcessMetadata $metadata,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $errors = [];

        if (! isset($payload['name']) || ! is_string($payload['name']) || trim($payload['name']) === '') {
            $errors[] = 'The process "name" is required.';
        }

        if (! isset($payload['slug']) || ! is_string($payload['slug']) || $payload['slug'] === '') {
            $errors[] = 'The process "slug" is required.';
        } elseif (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $payload['slug'])) {
            $errors[] = 'The process "slug" must be kebab-case (lowercase letters, numbers, hyphens).';
        }

        if ($errors !== []) {
            throw InvalidProcessDefinitionException::withErrors($errors);
        }

        $status = ProcessStatus::tryFrom((string) ($payload['status'] ?? 'draft')) ?? ProcessStatus::Draft;

        /** @var list<array<string, mixed>> $nodePayloads */
        $nodePayloads = is_array($payload['nodes'] ?? null) ? array_values($payload['nodes']) : [];

        /** @var list<array<string, mixed>> $edgePayloads */
        $edgePayloads = is_array($payload['edges'] ?? null) ? array_values($payload['edges']) : [];

        /** @var array<string, mixed> $metadataPayload */
        $metadataPayload = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];

        $nodes = array_map(ProcessNode::fromArray(...), $nodePayloads);
        $edges = array_map(ProcessEdge::fromArray(...), $edgePayloads);

        self::assertUniqueIds($nodes, $edges);

        return new self(
            schemaVersion: is_string($payload['schemaVersion'] ?? null) ? $payload['schemaVersion'] : self::SCHEMA_VERSION,
            id: is_string($payload['id'] ?? null) && $payload['id'] !== '' ? $payload['id'] : self::generateId(),
            name: $payload['name'],
            slug: $payload['slug'],
            description: isset($payload['description']) && is_string($payload['description']) ? $payload['description'] : null,
            version: is_int($payload['version'] ?? null) ? $payload['version'] : 1,
            status: $status,
            entryNodeId: isset($payload['entryNodeId']) && is_string($payload['entryNodeId']) ? $payload['entryNodeId'] : null,
            nodes: $nodes,
            edges: $edges,
            metadata: ProcessMetadata::fromArray($metadataPayload),
        );
    }

    /**
     * @param  list<ProcessNode>  $nodes
     * @param  list<ProcessEdge>  $edges
     */
    private static function assertUniqueIds(array $nodes, array $edges): void
    {
        $errors = [];

        $nodeIds = array_map(static fn (ProcessNode $node): string => $node->id, $nodes);

        if (count($nodeIds) !== count(array_unique($nodeIds))) {
            $errors[] = 'Node ids must be unique.';
        }

        $edgeIds = array_map(static fn (ProcessEdge $edge): string => $edge->id, $edges);

        if (count($edgeIds) !== count(array_unique($edgeIds))) {
            $errors[] = 'Edge ids must be unique.';
        }

        $knownNodeIds = array_flip($nodeIds);

        foreach ($edges as $edge) {
            if (! isset($knownNodeIds[$edge->source])) {
                $errors[] = "Edge [{$edge->id}] references a missing source node [{$edge->source}].";
            }

            if (! isset($knownNodeIds[$edge->target])) {
                $errors[] = "Edge [{$edge->id}] references a missing target node [{$edge->target}].";
            }
        }

        if ($errors !== []) {
            throw InvalidProcessDefinitionException::withErrors($errors);
        }
    }

    public function nodeById(string $id): ?ProcessNode
    {
        foreach ($this->nodes as $node) {
            if ($node->id === $id) {
                return $node;
            }
        }

        return null;
    }

    public function withIncrementedVersion(): self
    {
        return new self(
            $this->schemaVersion,
            $this->id,
            $this->name,
            $this->slug,
            $this->description,
            $this->version + 1,
            $this->status,
            $this->entryNodeId,
            $this->nodes,
            $this->edges,
            $this->metadata->withUpdatedNow(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schemaVersion' => $this->schemaVersion,
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'version' => $this->version,
            'status' => $this->status->value,
            'entryNodeId' => $this->entryNodeId,
            'nodes' => array_map(static fn (ProcessNode $node): array => $node->toArray(), $this->nodes),
            'edges' => array_map(static fn (ProcessEdge $edge): array => $edge->toArray(), $this->edges),
            'metadata' => $this->metadata->toArray(),
        ];
    }

    private static function generateId(): string
    {
        return strtoupper(bin2hex(random_bytes(13)));
    }
}
