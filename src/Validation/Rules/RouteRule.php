<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Validation\Rules;

use MohamedZaki\LaravelProcessBuilder\Contracts\ValidationRule;
use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationError;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationResult;
use MohamedZaki\LaravelProcessBuilder\Enums\NodeType;
use MohamedZaki\LaravelProcessBuilder\Graph\ProcessGraph;

final class RouteRule implements ValidationRule
{
    private const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    public function validate(ProcessDefinition $process): ValidationResult
    {
        $issues = [];
        $graph = new ProcessGraph($process);

        foreach ($process->nodes as $node) {
            if ($node->type !== NodeType::Route) {
                continue;
            }

            $issues = [...$issues, ...$this->validateRouteNode($node, $process, $graph)];
        }

        return ValidationResult::fromIssues($issues);
    }

    /**
     * @return list<ValidationError>
     */
    private function validateRouteNode(ProcessNode $node, ProcessDefinition $process, ProcessGraph $graph): array
    {
        $issues = [];

        $method = $node->data['method'] ?? null;

        if (! is_string($method) || ! in_array(strtoupper($method), self::ALLOWED_METHODS, strict: true)) {
            $issues[] = ValidationError::error(
                'route.invalid_method',
                'The route must use a supported HTTP method (GET, POST, PUT, PATCH, DELETE, OPTIONS).',
                nodeId: $node->id,
                field: 'method',
            );
        }

        $uri = $node->data['uri'] ?? null;

        if (! is_string($uri) || trim($uri) === '') {
            $issues[] = ValidationError::error(
                'route.invalid_uri',
                'The route must have a valid URI.',
                nodeId: $node->id,
                field: 'uri',
            );
        }

        $name = $node->data['name'] ?? null;

        if ($name !== null && (! is_string($name) || ! preg_match('/^[a-zA-Z0-9_.\-]+$/', $name))) {
            $issues[] = ValidationError::error(
                'route.invalid_name',
                'The route name must contain only letters, numbers, dots, dashes, and underscores.',
                nodeId: $node->id,
                field: 'name',
            );
        }

        $middleware = $node->data['middleware'] ?? [];

        if (! is_array($middleware)) {
            $issues[] = ValidationError::error(
                'route.invalid_middleware',
                'Middleware values must be a list of strings.',
                nodeId: $node->id,
                field: 'middleware',
            );
        } else {
            foreach ($middleware as $item) {
                if (! is_string($item) || trim($item) === '') {
                    $issues[] = ValidationError::error(
                        'route.invalid_middleware',
                        'Middleware values must be a list of strings.',
                        nodeId: $node->id,
                        field: 'middleware',
                    );

                    break;
                }
            }
        }

        $connectsToController = false;

        foreach ($graph->reachableFrom($node->id) as $reachableId => $_) {
            $reachableNode = $process->nodeById((string) $reachableId);

            if ($reachableNode !== null && $reachableNode->type === NodeType::Controller) {
                $connectsToController = true;

                break;
            }
        }

        if (! $connectsToController) {
            $issues[] = ValidationError::error(
                'route.controller_missing',
                'The route must connect to a controller node.',
                nodeId: $node->id,
            );
        }

        return $issues;
    }
}
