<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class FileOwnershipConflictException extends ProcessBuilderException
{
    public static function forPath(string $relativePath): self
    {
        return new self("The file [{$relativePath}] is not managed by Laravel Process Builder and cannot be overwritten.");
    }

    protected function httpStatus(): int
    {
        return 409;
    }

    protected function errorCode(): string
    {
        return 'process.file_ownership_conflict';
    }
}
