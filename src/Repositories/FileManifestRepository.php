<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Repositories;

use MohamedZaki\LaravelProcessBuilder\Contracts\ManifestRepository;
use MohamedZaki\LaravelProcessBuilder\DTO\GenerationManifest;
use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidProcessDefinitionException;
use MohamedZaki\LaravelProcessBuilder\Filesystem\SafePath;

final class FileManifestRepository implements ManifestRepository
{
    public function __construct(private readonly string $directory)
    {
    }

    public function find(string $processSlug): ?GenerationManifest
    {
        if (! SafePath::isSafeSlug($processSlug)) {
            return null;
        }

        $path = SafePath::resolveWithin($this->directory, $processSlug.'.json');

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        /** @var mixed $payload */
        $payload = json_decode($contents, true);

        if (! is_array($payload)) {
            return null;
        }

        return GenerationManifest::fromArray($payload);
    }

    public function save(string $processSlug, GenerationManifest $manifest): void
    {
        $this->ensureDirectoryExists();

        $path = SafePath::resolveWithin($this->directory, $processSlug.'.json');

        $json = json_encode($manifest->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw InvalidProcessDefinitionException::withErrors(['Failed to encode generation manifest as JSON.']);
        }

        $tempPath = $path.'.tmp';

        $handle = fopen($tempPath, 'w');

        if ($handle === false) {
            throw InvalidProcessDefinitionException::withErrors(["Unable to open [{$tempPath}] for writing."]);
        }

        try {
            flock($handle, LOCK_EX);
            fwrite($handle, $json.PHP_EOL);
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        rename($tempPath, $path);
    }

    private function ensureDirectoryExists(): void
    {
        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }
}
