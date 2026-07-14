<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

final class ControllerInfo
{
    /**
     * @param  list<MethodInfo>  $methods
     * @param  list<ParameterInfo>  $constructorDependencies
     */
    public function __construct(
        public readonly string $class,
        public readonly bool $exists,
        public readonly ?string $filePath,
        public readonly bool $isInvokable,
        public readonly array $methods,
        public readonly array $constructorDependencies,
    ) {
    }

    public function methodByName(string $name): ?MethodInfo
    {
        foreach ($this->methods as $method) {
            if ($method->name === $name) {
                return $method;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'exists' => $this->exists,
            'filePath' => $this->filePath,
            'isInvokable' => $this->isInvokable,
            'methods' => array_map(
                static fn (MethodInfo $method): array => $method->toArray(),
                $this->methods,
            ),
            'constructorDependencies' => array_map(
                static fn (ParameterInfo $parameter): array => $parameter->toArray(),
                $this->constructorDependencies,
            ),
        ];
    }
}
