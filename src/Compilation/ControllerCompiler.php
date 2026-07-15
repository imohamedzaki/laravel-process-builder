<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Compilation;

use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;

final class ControllerCompiler
{
    public function __construct(
        private readonly StubRenderer $renderer,
        private readonly ClassNameResolver $resolver,
    ) {
    }

    public function compile(
        ProcessNode $node,
        string $defaultNamespace,
        ?string $formRequestClass,
        ?string $actionClass,
        ?string $resourceClass,
    ): string {
        $class = $this->resolver->shortClassName($node, '');
        $namespace = $this->resolver->namespace($node, $defaultNamespace);
        $method = is_string($node->data['method'] ?? null) ? $node->data['method'] : 'store';

        $imports = ['use App\\Http\\Controllers\\Controller;'];
        $parameters = [];

        if ($formRequestClass !== null) {
            $imports[] = "use {$formRequestClass};";
            $parameters[] = "{$this->shortName($formRequestClass)} \$request";
        }

        if ($actionClass !== null) {
            $imports[] = "use {$actionClass};";
            $parameters[] = "{$this->shortName($actionClass)} \$action";
        }

        if ($resourceClass !== null) {
            $imports[] = "use {$resourceClass};";
        }

        $imports[] = 'use Illuminate\Http\JsonResponse;';

        sort($imports);

        $body = $actionClass !== null
            ? $this->actionInvocationBody($formRequestClass !== null, $resourceClass)
            : '        return response()->json([]);';

        return $this->renderer->render('controller', [
            'namespace' => $namespace,
            'imports' => implode("\n", $imports)."\n",
            'class' => $class,
            'method' => $method,
            'parameters' => implode(', ', $parameters),
            'returnType' => 'JsonResponse',
            'body' => $body,
        ]);
    }

    private function actionInvocationBody(bool $hasFormRequest, ?string $resourceClass): string
    {
        $lines = [];

        $argument = $hasFormRequest ? '$request->validated()' : '[]';
        $lines[] = "        \$result = \$action->execute({$argument});";
        $lines[] = '';

        if ($resourceClass !== null) {
            $lines[] = "        return response()->json(new {$this->shortName($resourceClass)}(\$result), 201);";
        } else {
            $lines[] = '        return response()->json($result, 201);';
        }

        return implode("\n", $lines);
    }

    private function shortName(string $fqcn): string
    {
        return str_contains($fqcn, '\\') ? (string) substr((string) strrchr($fqcn, '\\'), 1) : $fqcn;
    }
}
