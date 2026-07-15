<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Compilation;

use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;

final class ActionCompiler
{
    public function __construct(
        private readonly StubRenderer $renderer,
        private readonly ClassNameResolver $resolver,
    ) {
    }

    /**
     * @param  list<string>  $bodyStatements  pre-rendered PHP statement lines, already 8-space indented
     */
    public function compile(
        ProcessNode $node,
        string $defaultNamespace,
        ?string $modelClass,
        array $bodyStatements,
        bool $transactional,
    ): string {
        $class = $this->resolver->shortClassName($node, '');
        $namespace = $this->resolver->namespace($node, $defaultNamespace);
        $method = is_string($node->data['method'] ?? null) ? $node->data['method'] : 'execute';

        $imports = [];

        if ($modelClass !== null) {
            $imports[] = "use {$modelClass};";
        }

        if ($transactional) {
            $imports[] = 'use Illuminate\Support\Facades\DB;';
        }

        sort($imports);

        $body = $bodyStatements === []
            ? '        return true;'
            : implode("\n", $bodyStatements);

        if ($transactional) {
            $body = "        return DB::transaction(function () {\n".
                implode("\n", array_map(static fn (string $line): string => '    '.$line, $bodyStatements)).
                "\n        });";
        }

        return $this->renderer->render('action', [
            'namespace' => $namespace,
            'imports' => $imports === [] ? '' : implode("\n", $imports)."\n",
            'class' => $class,
            'method' => $method,
            'parameters' => 'array $data',
            'returnType' => $modelClass !== null ? $this->shortName($modelClass) : 'mixed',
            'body' => $body,
        ]);
    }

    private function shortName(string $fqcn): string
    {
        return str_contains($fqcn, '\\') ? (string) substr((string) strrchr($fqcn, '\\'), 1) : $fqcn;
    }
}
