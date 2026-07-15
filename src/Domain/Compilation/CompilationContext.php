<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Domain\Compilation;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;

final class CompilationContext
{
    /**
     * @param  array<string, string>  $resolvedControllerClasses  node id => fully-qualified class name
     * @param  array<string, string>  $resolvedActionClasses
     */
    public function __construct(
        public readonly ProcessDefinition $process,
        public readonly array $resolvedControllerClasses = [],
        public readonly array $resolvedActionClasses = [],
    ) {
    }
}
