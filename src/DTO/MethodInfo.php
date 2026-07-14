<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

final class MethodInfo
{
    /**
     * @param  list<ParameterInfo>  $parameters
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $exists,
        public readonly bool $isPublic,
        public readonly array $parameters,
        public readonly ?string $returnType,
        public readonly ?string $formRequestParameter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'exists' => $this->exists,
            'isPublic' => $this->isPublic,
            'parameters' => array_map(
                static fn (ParameterInfo $parameter): array => $parameter->toArray(),
                $this->parameters,
            ),
            'returnType' => $this->returnType,
            'formRequestParameter' => $this->formRequestParameter,
        ];
    }
}
