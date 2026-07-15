<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class PhpSyntaxException extends ProcessBuilderException
{
    public static function forFile(string $relativePath, string $reason): self
    {
        return new self("Generated file [{$relativePath}] failed PHP syntax validation: {$reason}");
    }

    protected function httpStatus(): int
    {
        return 500;
    }

    protected function errorCode(): string
    {
        return 'process.php_syntax_error';
    }
}
