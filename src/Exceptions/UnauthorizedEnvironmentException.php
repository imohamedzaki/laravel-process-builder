<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class UnauthorizedEnvironmentException extends ProcessBuilderException
{
    public static function forEnvironment(string $environment): self
    {
        return new self("The current environment [{$environment}] is not authorized to use Laravel Process Builder.");
    }

    protected function httpStatus(): int
    {
        return 403;
    }

    protected function errorCode(): string
    {
        return 'process.unauthorized_environment';
    }
}
