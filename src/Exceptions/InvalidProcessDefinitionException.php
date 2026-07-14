<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class InvalidProcessDefinitionException extends ProcessBuilderException
{
    /**
     * @param  list<string>  $errors
     */
    public static function withErrors(array $errors): self
    {
        return new self('Invalid process definition: '.implode('; ', $errors));
    }

    protected function httpStatus(): int
    {
        return 422;
    }

    protected function errorCode(): string
    {
        return 'process.invalid_definition';
    }
}
