<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class ProcessNotFoundException extends ProcessBuilderException
{
    public static function forIdentifier(string $idOrSlug): self
    {
        return new self("No process definition found for identifier [{$idOrSlug}].");
    }

    protected function httpStatus(): int
    {
        return 404;
    }

    protected function errorCode(): string
    {
        return 'process.not_found';
    }
}
