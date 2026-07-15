<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Compilation;

use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;

final class FeatureTestCompiler
{
    public function __construct(private readonly StubRenderer $renderer)
    {
    }

    public function compile(ProcessNode $routeNode, string $defaultNamespace, int $expectedStatus): string
    {
        $method = strtolower((string) ($routeNode->data['method'] ?? 'get'));
        $uri = (string) ($routeNode->data['uri'] ?? '/');
        $name = is_string($routeNode->data['name'] ?? null) && $routeNode->data['name'] !== ''
            ? $routeNode->data['name']
            : $routeNode->id;

        $class = $this->studly($name).'Test';
        $methodName = str_replace('-', '_', $this->snake($name));

        $httpMethod = match ($method) {
            'post' => 'postJson',
            'put' => 'putJson',
            'patch' => 'patchJson',
            'delete' => 'deleteJson',
            default => 'getJson',
        };

        $payload = in_array($httpMethod, ['postJson', 'putJson', 'patchJson'], strict: true) ? ', []' : '';

        return $this->renderer->render('feature-test', [
            'namespace' => $defaultNamespace,
            'class' => $class,
            'methodName' => $methodName,
            'httpMethod' => $httpMethod,
            'uri' => $uri,
            'payload' => $payload,
            'expectedStatus' => (string) $expectedStatus,
        ]);
    }

    private function studly(string $value): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9]+/', ' ', $value) ?? $value;

        return str_replace(' ', '', ucwords($normalized));
    }

    private function snake(string $value): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9]+/', '_', $value) ?? $value;

        return strtolower(trim($normalized, '_'));
    }
}
