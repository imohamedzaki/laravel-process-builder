<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Domain\Participants;

use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidProcessDefinitionException;

final class ProcessParticipant
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $guard,
        public readonly ?string $actorType,
        public readonly int $order,
        public readonly ?string $color,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        foreach (['id', 'name', 'guard'] as $required) {
            if (! isset($payload[$required]) || ! is_string($payload[$required]) || trim($payload[$required]) === '') {
                throw InvalidProcessDefinitionException::withErrors(["A participant is missing a valid \"{$required}\"."]);
            }
        }

        if (preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $payload['guard']) !== 1) {
            throw InvalidProcessDefinitionException::withErrors([
                "Participant [{$payload['id']}] has an invalid guard identifier.",
            ]);
        }

        $actorType = $payload['actorType'] ?? null;
        if ($actorType !== null && ! in_array($actorType, ['human', 'system'], true)) {
            throw InvalidProcessDefinitionException::withErrors([
                "Participant [{$payload['id']}] has an invalid actor type.",
            ]);
        }

        return new self(
            id: $payload['id'],
            name: $payload['name'],
            guard: $payload['guard'],
            actorType: $actorType,
            order: isset($payload['order']) && is_int($payload['order']) ? $payload['order'] : 0,
            color: isset($payload['color']) && is_string($payload['color']) ? $payload['color'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard' => $this->guard,
            'actorType' => $this->actorType,
            'order' => $this->order,
            'color' => $this->color,
        ];
    }
}
