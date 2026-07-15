<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

final class GenerationResult
{
    public function __construct(
        public readonly GeneratedFileCollection $files,
        public readonly ?BackupMetadata $backup,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'files' => $this->files->toArray(),
            'backup' => $this->backup?->toArray(),
        ];
    }
}
