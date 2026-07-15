<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Domain\Lanes;

use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidProcessDefinitionException;

final class ProcessLane
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $actorType,
        public readonly int $order,
        public readonly ?string $color,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        foreach (['id', 'name'] as $required) {
            if (! isset($payload[$required]) || ! is_string($payload[$required]) || $payload[$required] === '') {
                throw InvalidProcessDefinitionException::withErrors(["A lane is missing a valid \"{$required}\"."]);
            }
        }

        $actorType = $payload['actorType'] ?? null;

        if ($actorType !== null && ! in_array($actorType, ['human', 'system'], true)) {
            throw InvalidProcessDefinitionException::withErrors([
                "Lane [{$payload['id']}] has an invalid \"actorType\" (expected \"human\", \"system\", or null).",
            ]);
        }

        return new self(
            id: $payload['id'],
            name: $payload['name'],
            actorType: $actorType,
            order: isset($payload['order']) && is_int($payload['order']) ? $payload['order'] : 0,
            color: isset($payload['color']) && is_string($payload['color']) ? $payload['color'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'actorType' => $this->actorType,
            'order' => $this->order,
            'color' => $this->color,
        ];
    }
}
