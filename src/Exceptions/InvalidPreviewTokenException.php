<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class InvalidPreviewTokenException extends ProcessBuilderException
{
    public static function expired(): self
    {
        return new self('The preview confirmation token has expired. Re-run preview before generating.');
    }

    public static function invalid(): self
    {
        return new self('The preview confirmation token is invalid.');
    }

    protected function httpStatus(): int
    {
        return 422;
    }

    protected function errorCode(): string
    {
        return 'process.invalid_preview_token';
    }
}
