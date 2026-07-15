<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class RollbackFailedException extends ProcessBuilderException
{
    protected function httpStatus(): int
    {
        return 500;
    }

    protected function errorCode(): string
    {
        return 'process.rollback_failed';
    }
}
