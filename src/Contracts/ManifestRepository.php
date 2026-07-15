<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Contracts;

use MohamedZaki\LaravelProcessBuilder\DTO\GenerationManifest;

interface ManifestRepository
{
    public function find(string $processSlug): ?GenerationManifest;

    public function save(string $processSlug, GenerationManifest $manifest): void;
}
