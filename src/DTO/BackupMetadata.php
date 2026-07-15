<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

final class BackupMetadata
{
    /**
     * @param  list<string>  $backedUpRelativePaths
     */
    public function __construct(
        public readonly string $id,
        public readonly string $processSlug,
        public readonly string $createdAt,
        public readonly array $backedUpRelativePaths,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: (string) ($payload['id'] ?? ''),
            processSlug: (string) ($payload['processSlug'] ?? ''),
            createdAt: (string) ($payload['createdAt'] ?? ''),
            backedUpRelativePaths: is_array($payload['backedUpRelativePaths'] ?? null)
                ? array_values(array_map('strval', $payload['backedUpRelativePaths']))
                : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'processSlug' => $this->processSlug,
            'createdAt' => $this->createdAt,
            'backedUpRelativePaths' => $this->backedUpRelativePaths,
        ];
    }
}
