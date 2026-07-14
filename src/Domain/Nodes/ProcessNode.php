<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Domain\Nodes;

use MohamedZaki\LaravelProcessBuilder\Enums\NodeType;
use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidProcessDefinitionException;

final class ProcessNode
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $id,
        public readonly NodeType $type,
        public readonly NodePosition $position,
        public readonly array $data,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        if (! isset($payload['id']) || ! is_string($payload['id']) || $payload['id'] === '') {
            throw InvalidProcessDefinitionException::withErrors(['A node is missing a valid "id".']);
        }

        if (! isset($payload['type']) || ! is_string($payload['type'])) {
            throw InvalidProcessDefinitionException::withErrors(["Node [{$payload['id']}] is missing a valid \"type\"."]);
        }

        $type = NodeType::tryFrom($payload['type']);

        if ($type === null) {
            throw InvalidProcessDefinitionException::withErrors(["Node [{$payload['id']}] has an unknown type \"{$payload['type']}\"."]);
        }

        /** @var array<string, mixed> $position */
        $position = is_array($payload['position'] ?? null) ? $payload['position'] : [];

        /** @var array<string, mixed> $data */
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        return new self(
            id: $payload['id'],
            type: $type,
            position: NodePosition::fromArray($position),
            data: $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'position' => $this->position->toArray(),
            'data' => $this->data,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function withData(array $data): self
    {
        return new self($this->id, $this->type, $this->position, $data);
    }
}
