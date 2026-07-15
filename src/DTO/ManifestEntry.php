<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

final class ManifestEntry
{
    public function __construct(
        public readonly string $logicalType,
        public readonly string $relativePath,
        public readonly string $absolutePath,
        public readonly string $sha256,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            logicalType: (string) ($payload['logicalType'] ?? ''),
            relativePath: (string) ($payload['relativePath'] ?? ''),
            absolutePath: (string) ($payload['absolutePath'] ?? ''),
            sha256: (string) ($payload['sha256'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'logicalType' => $this->logicalType,
            'relativePath' => $this->relativePath,
            'absolutePath' => $this->absolutePath,
            'sha256' => $this->sha256,
        ];
    }
}
