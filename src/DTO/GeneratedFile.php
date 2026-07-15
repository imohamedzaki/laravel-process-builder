<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

final class GeneratedFile
{
    public function __construct(
        public readonly string $logicalType,
        public readonly string $relativePath,
        public readonly string $absolutePath,
        public readonly string $contents,
    ) {
    }

    public function checksum(): string
    {
        return hash('sha256', $this->contents);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'logicalType' => $this->logicalType,
            'relativePath' => $this->relativePath,
            'contents' => $this->contents,
            'sha256' => $this->checksum(),
        ];
    }
}
