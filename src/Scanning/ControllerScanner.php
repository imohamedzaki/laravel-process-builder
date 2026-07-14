<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Scanning;

use MohamedZaki\LaravelProcessBuilder\Contracts\ControllerScannerContract;
use MohamedZaki\LaravelProcessBuilder\DTO\ControllerInfo;
use MohamedZaki\LaravelProcessBuilder\DTO\MethodInfo;
use MohamedZaki\LaravelProcessBuilder\DTO\ParameterInfo;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

final class ControllerScanner implements ControllerScannerContract
{
    public function inspect(string $class): ControllerInfo
    {
        if (! class_exists($class)) {
            return new ControllerInfo(
                class: $class,
                exists: false,
                filePath: null,
                isInvokable: false,
                methods: [],
                constructorDependencies: [],
            );
        }

        $reflection = new ReflectionClass($class);

        $constructor = $reflection->getConstructor();

        return new ControllerInfo(
            class: $class,
            exists: true,
            filePath: $reflection->getFileName() ?: null,
            isInvokable: $reflection->hasMethod('__invoke'),
            methods: $this->publicMethods($reflection),
            constructorDependencies: $constructor === null
                ? []
                : $this->parametersOf($constructor),
        );
    }

    public function inspectMethod(string $class, string $method): MethodInfo
    {
        if (! class_exists($class)) {
            return $this->missingMethod($method);
        }

        $reflection = new ReflectionClass($class);

        if (! $reflection->hasMethod($method)) {
            return $this->missingMethod($method);
        }

        return $this->toMethodInfo($reflection->getMethod($method));
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     * @return list<MethodInfo>
     */
    private function publicMethods(ReflectionClass $reflection): array
    {
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isStatic() || $method->isAbstract()) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            $methods[] = $this->toMethodInfo($method);
        }

        return $methods;
    }

    private function toMethodInfo(ReflectionMethod $method): MethodInfo
    {
        $parameters = $this->parametersOf($method);

        return new MethodInfo(
            name: $method->getName(),
            exists: true,
            isPublic: $method->isPublic(),
            parameters: $parameters,
            returnType: $this->typeToString($method->getReturnType()),
            formRequestParameter: $this->findFormRequestParameter($parameters),
        );
    }

    /**
     * @return list<ParameterInfo>
     */
    private function parametersOf(ReflectionMethod $method): array
    {
        return array_values(array_map(
            function (ReflectionParameter $parameter): ParameterInfo {
                return new ParameterInfo(
                    name: $parameter->getName(),
                    type: $this->typeToString($parameter->getType()),
                    isOptional: $parameter->isOptional(),
                    hasDefault: $parameter->isDefaultValueAvailable(),
                    isVariadic: $parameter->isVariadic(),
                );
            },
            $method->getParameters(),
        ));
    }

    /**
     * @param  list<ParameterInfo>  $parameters
     */
    private function findFormRequestParameter(array $parameters): ?string
    {
        foreach ($parameters as $parameter) {
            if ($parameter->type === null) {
                continue;
            }

            if (is_a($parameter->type, 'Illuminate\Foundation\Http\FormRequest', true)
                || str_ends_with($parameter->type, 'Request')
            ) {
                return $parameter->type;
            }
        }

        return null;
    }

    private function typeToString(mixed $type): ?string
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        return $type === null ? null : (string) $type;
    }

    private function missingMethod(string $method): MethodInfo
    {
        return new MethodInfo(
            name: $method,
            exists: false,
            isPublic: false,
            parameters: [],
            returnType: null,
            formRequestParameter: null,
        );
    }
}
