<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class GenerationDisabledException extends ProcessBuilderException
{
    public static function create(): self
    {
        return new self('Code generation is disabled. Enable it via PROCESS_BUILDER_GENERATION_ENABLED.');
    }
}
