<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Compilation;

use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;

final class JobCompiler
{
    public function __construct(
        private readonly StubRenderer $renderer,
        private readonly ClassNameResolver $resolver,
    ) {
    }

    public function compile(ProcessNode $node, string $defaultNamespace, ?string $modelClass): string
    {
        $class = $this->resolver->shortClassName($node, '');
        $namespace = $this->resolver->namespace($node, $defaultNamespace);

        $parameters = [];

        if ($modelClass !== null) {
            $shortModel = $this->shortName($modelClass);
            $parameters[] = "private readonly {$shortModel} \$model";
        }

        return $this->renderer->render('job', [
            'namespace' => $namespace,
            'class' => $class,
            'parameters' => implode(', ', $parameters),
            'constructorBody' => '        //',
            'body' => '        //',
        ]);
    }

    private function shortName(string $fqcn): string
    {
        return str_contains($fqcn, '\\') ? (string) substr((string) strrchr($fqcn, '\\'), 1) : $fqcn;
    }
}
