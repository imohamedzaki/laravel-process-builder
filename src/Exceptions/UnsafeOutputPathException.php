<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class UnsafeOutputPathException extends ProcessBuilderException
{
    public static function outsideManagedDirectory(string $path): self
    {
        return new self("The path [{$path}] is outside of the managed output directories.");
    }

    public static function traversalDetected(string $path): self
    {
        return new self("Path traversal detected in [{$path}].");
    }

    protected function httpStatus(): int
    {
        return 422;
    }

    protected function errorCode(): string
    {
        return 'process.unsafe_path';
    }
}
