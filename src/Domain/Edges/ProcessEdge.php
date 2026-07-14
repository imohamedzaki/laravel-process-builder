<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Domain\Edges;

use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidProcessDefinitionException;

final class ProcessEdge
{
    public function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly ?string $sourceHandle,
        public readonly string $target,
        public readonly ?string $targetHandle,
        public readonly ?string $label,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        foreach (['id', 'source', 'target'] as $required) {
            if (! isset($payload[$required]) || ! is_string($payload[$required]) || $payload[$required] === '') {
                throw InvalidProcessDefinitionException::withErrors(["An edge is missing a valid \"{$required}\"."]);
            }
        }

        return new self(
            id: $payload['id'],
            source: $payload['source'],
            sourceHandle: isset($payload['sourceHandle']) && is_string($payload['sourceHandle']) ? $payload['sourceHandle'] : null,
            target: $payload['target'],
            targetHandle: isset($payload['targetHandle']) && is_string($payload['targetHandle']) ? $payload['targetHandle'] : null,
            label: isset($payload['label']) && is_string($payload['label']) ? $payload['label'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'sourceHandle' => $this->sourceHandle,
            'target' => $this->target,
            'targetHandle' => $this->targetHandle,
            'label' => $this->label,
        ];
    }
}
