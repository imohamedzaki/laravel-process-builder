<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Compilation;

use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;

final class ApiResourceCompiler
{
    public function __construct(
        private readonly StubRenderer $renderer,
        private readonly ClassNameResolver $resolver,
    ) {
    }

    public function compile(ProcessNode $node, string $defaultNamespace): string
    {
        $class = $this->resolver->shortClassName($node, '');
        $namespace = $this->resolver->namespace($node, $defaultNamespace);

        return $this->renderer->render('resource', [
            'namespace' => $namespace,
            'class' => $class,
            'attributes' => '$this->resource->toArray()',
        ]);
    }
}
