<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

final class ParameterInfo
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $type,
        public readonly bool $isOptional,
        public readonly bool $hasDefault,
        public readonly bool $isVariadic,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'isOptional' => $this->isOptional,
            'hasDefault' => $this->hasDefault,
            'isVariadic' => $this->isVariadic,
        ];
    }
}
