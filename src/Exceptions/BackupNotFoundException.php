<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class BackupNotFoundException extends ProcessBuilderException
{
    public static function forId(string $backupId): self
    {
        return new self("No backup found with id [{$backupId}].");
    }

    protected function httpStatus(): int
    {
        return 404;
    }

    protected function errorCode(): string
    {
        return 'process.backup_not_found';
    }
}
