<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Domain\Processes;

use DateTimeImmutable;
use MohamedZaki\LaravelProcessBuilder\Domain\Edges\ProcessEdge;
use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;
use MohamedZaki\LaravelProcessBuilder\Domain\Participants\ProcessParticipant;
use MohamedZaki\LaravelProcessBuilder\Enums\ProcessStatus;
use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidProcessDefinitionException;

final class ProcessDefinition
{
    public const SCHEMA_VERSION = '1.3';

    /**
     * @param  list<ProcessNode>  $nodes
     * @param  list<ProcessEdge>  $edges
     * @param  list<ProcessParticipant>  $participants
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
        public readonly array $participants,
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

        /** @var list<array<string, mixed>> $participantPayloads */
        $participantPayloads = is_array($payload['participants'] ?? null)
            ? array_values($payload['participants'])
            : self::migrateLegacyLanes($payload);

        $nodePayloads = self::migrateLegacyNodeAssignments($nodePayloads, $participantPayloads);

        /** @var array<string, mixed> $metadataPayload */
        $metadataPayload = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];

        $nodes = array_values(array_map(ProcessNode::fromArray(...), $nodePayloads));
        $edges = array_values(array_map(ProcessEdge::fromArray(...), $edgePayloads));
        $participants = array_values(array_map(ProcessParticipant::fromArray(...), $participantPayloads));

        self::assertUniqueIds($nodes, $edges, $participants);

        return new self(
            schemaVersion: self::SCHEMA_VERSION,
            id: is_string($payload['id'] ?? null) && $payload['id'] !== '' ? $payload['id'] : self::generateId(),
            name: $payload['name'],
            slug: $payload['slug'],
            description: isset($payload['description']) && is_string($payload['description']) ? $payload['description'] : null,
            version: is_int($payload['version'] ?? null) ? $payload['version'] : 1,
            status: $status,
            entryNodeId: isset($payload['entryNodeId']) && is_string($payload['entryNodeId']) ? $payload['entryNodeId'] : null,
            nodes: $nodes,
            edges: $edges,
            participants: $participants,
            metadata: ProcessMetadata::fromArray($metadataPayload),
        );
    }

    /**
     * @param  list<ProcessNode>  $nodes
     * @param  list<ProcessEdge>  $edges
     * @param  list<ProcessParticipant>  $participants
     */
    private static function assertUniqueIds(array $nodes, array $edges, array $participants): void
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

        $participantIds = array_map(static fn (ProcessParticipant $participant): string => $participant->id, $participants);

        if (count($participantIds) !== count(array_unique($participantIds))) {
            $errors[] = 'Participant ids must be unique.';
        }

        $guards = array_map(static fn (ProcessParticipant $participant): string => $participant->guard, $participants);
        if (count($guards) !== count(array_unique($guards))) {
            $errors[] = 'Participant guards must be unique within a process.';
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

    public function participantById(string $id): ?ProcessParticipant
    {
        foreach ($this->participants as $participant) {
            if ($participant->id === $id) {
                return $participant;
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
            $this->participants,
            $this->metadata->withUpdatedNow(),
        );
    }

    public function withGenerated(string $generatorVersion): self
    {
        return new self(
            $this->schemaVersion,
            $this->id,
            $this->name,
            $this->slug,
            $this->description,
            $this->version,
            ProcessStatus::Generated,
            $this->entryNodeId,
            $this->nodes,
            $this->edges,
            $this->participants,
            $this->metadata->withGenerated(new DateTimeImmutable(), $generatorVersion),
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
            'participants' => array_map(static fn (ProcessParticipant $participant): array => $participant->toArray(), $this->participants),
            'metadata' => $this->metadata->toArray(),
        ];
    }

    private static function generateId(): string
    {
        return strtoupper(bin2hex(random_bytes(13)));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private static function migrateLegacyLanes(array $payload): array
    {
        if (! is_array($payload['lanes'] ?? null)) {
            return [];
        }

        return array_map(static function (mixed $lane) use ($payload): array {
            $lane = is_array($lane) ? $lane : [];
            $fallback = strtolower((string) ($lane['name'] ?? $payload['guard'] ?? 'participant'));
            $guard = preg_replace('/[^a-z0-9]+/', '-', $fallback) ?? 'participant';

            return array_merge($lane, ['guard' => trim($guard, '-') ?: 'participant']);
        }, array_values($payload['lanes']));
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  list<array<string, mixed>>  $participants
     * @return list<array<string, mixed>>
     */
    private static function migrateLegacyNodeAssignments(array $nodes, array $participants): array
    {
        $onlyParticipantId = count($participants) === 1 && is_string($participants[0]['id'] ?? null) ? $participants[0]['id'] : null;

        return array_map(static function (array $node) use ($onlyParticipantId): array {
            if (is_array($node['data'] ?? null) && ! isset($node['data']['participantId']) && isset($node['data']['laneId'])) {
                $node['data']['participantId'] = $node['data']['laneId'];
                unset($node['data']['laneId']);
            }
            if (is_array($node['data'] ?? null) && ! isset($node['data']['participantId']) && $onlyParticipantId !== null) {
                $node['data']['participantId'] = $onlyParticipantId;
            }

            return $node;
        }, $nodes);
    }
}
