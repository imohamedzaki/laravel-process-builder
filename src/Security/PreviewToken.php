<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Security;

final class PreviewToken
{
    public function __construct(
        public readonly string $processId,
        public readonly int $processVersion,
        public readonly string $definitionChecksum,
        public readonly int $expiresAt,
    ) {
    }

    public function isExpired(): bool
    {
        return time() > $this->expiresAt;
    }
}
