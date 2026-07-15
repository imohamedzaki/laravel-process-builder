<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

final class CompilationResult
{
    public function __construct(
        public readonly GeneratedFileCollection $files,
        public readonly ValidationResult $validation,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->validation->isValid();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'files' => $this->files->toArray(),
            'validation' => $this->validation->toArray(),
        ];
    }
}
