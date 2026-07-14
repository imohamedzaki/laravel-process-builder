<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Domain\Nodes;

final class NodePosition
{
    public function __construct(
        public readonly float $x,
        public readonly float $y,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            x: (float) ($data['x'] ?? 0),
            y: (float) ($data['y'] ?? 0),
        );
    }

    /**
     * @return array{x: float, y: float}
     */
    public function toArray(): array
    {
        return ['x' => $this->x, 'y' => $this->y];
    }
}
