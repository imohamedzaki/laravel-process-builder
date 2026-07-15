<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Compilation;

use MohamedZaki\LaravelProcessBuilder\Domain\Compilation\CompilationContext;
use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;
use MohamedZaki\LaravelProcessBuilder\Enums\NodeType;

final class RouteFileCompiler
{
    private const MARKER = <<<'PHP'
        /**
         * This file is managed by Laravel Process Builder.
         * Manual changes may be overwritten.
         */
        PHP;

    /**
     * Compile every route node in the process into a single managed routes file body.
     *
     * @param  array<string, array{class: string, method: string}>  $routeControllerActions  route node id => controller class + method
     */
    public function compile(CompilationContext $context, array $routeControllerActions): string
    {
        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            self::MARKER,
            '',
            'use Illuminate\Support\Facades\Route;',
        ];

        $usedControllers = [];

        foreach ($context->process->nodes as $node) {
            if ($node->type !== NodeType::Route) {
                continue;
            }

            $controllerClass = $routeControllerActions[$node->id]['class'] ?? null;

            if ($controllerClass !== null && ! in_array($controllerClass, $usedControllers, strict: true)) {
                $usedControllers[] = $controllerClass;
            }
        }

        sort($usedControllers);

        foreach ($usedControllers as $class) {
            $lines[] = "use {$class};";
        }

        $lines[] = '';

        foreach ($context->process->nodes as $node) {
            if ($node->type !== NodeType::Route) {
                continue;
            }

            $lines[] = $this->compileRouteStatement($node, $routeControllerActions[$node->id] ?? null);
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    /**
     * @param  array{class: string, method: string}|null  $controllerAction
     */
    private function compileRouteStatement(ProcessNode $node, ?array $controllerAction): string
    {
        $method = strtolower((string) ($node->data['method'] ?? 'get'));
        $uri = (string) ($node->data['uri'] ?? '/');
        $name = $node->data['name'] ?? null;
        $middleware = $node->data['middleware'] ?? [];

        $action = $controllerAction !== null
            ? '['.$this->shortClassName($controllerAction['class']).'::class, \''.$controllerAction['method'].'\']'
            : 'fn () => abort(501)';

        $statement = 'Route::';

        if (is_array($middleware) && $middleware !== []) {
            $middlewareList = implode("', '", array_map('strval', $middleware));
            $statement .= "middleware(['{$middlewareList}'])\n    ->{$method}('{$uri}', {$action})";
        } else {
            $statement .= "{$method}('{$uri}', {$action})";
        }

        if (is_string($name) && $name !== '') {
            $statement .= "\n    ->name('{$name}')";
        }

        return $statement.';';
    }

    private function shortClassName(string $fqcn): string
    {
        return str_contains($fqcn, '\\') ? (string) substr((string) strrchr($fqcn, '\\'), 1) : $fqcn;
    }
}
