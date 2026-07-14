<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class PhpSyntaxException extends ProcessBuilderException
{
    public static function forFile(string $relativePath, string $reason): self
    {
        return new self("Generated file [{$relativePath}] failed PHP syntax validation: {$reason}");
    }
}
