<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Repositories;

use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessCollection;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidProcessDefinitionException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessNotFoundException;
use MohamedZaki\LaravelProcessBuilder\Filesystem\SafePath;

final class FileProcessRepository implements ProcessRepository
{
    public function __construct(private readonly string $directory)
    {
    }

    public function all(): ProcessCollection
    {
        $this->ensureDirectoryExists();

        $processes = [];

        foreach (glob($this->directory.DIRECTORY_SEPARATOR.'*.json') ?: [] as $file) {
            $processes[] = $this->hydrateFile($file);
        }

        usort($processes, static fn (ProcessDefinition $a, ProcessDefinition $b): int => $a->name <=> $b->name);

        return new ProcessCollection($processes);
    }

    public function find(string $idOrSlug): ?ProcessDefinition
    {
        $this->ensureDirectoryExists();

        if (SafePath::isSafeSlug($idOrSlug)) {
            $bySlug = $this->pathForSlug($idOrSlug);

            if (is_file($bySlug)) {
                return $this->hydrateFile($bySlug);
            }
        }

        foreach ($this->all() as $process) {
            if ($process->id === $idOrSlug) {
                return $process;
            }
        }

        return null;
    }

    public function save(ProcessDefinition $process): void
    {
        $this->ensureDirectoryExists();

        $path = $this->pathForSlug($process->slug);

        $json = json_encode($process->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw InvalidProcessDefinitionException::withErrors(['Failed to encode process definition as JSON.']);
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

    public function delete(string $idOrSlug): void
    {
        $process = $this->find($idOrSlug);

        if ($process === null) {
            throw ProcessNotFoundException::forIdentifier($idOrSlug);
        }

        $path = $this->pathForSlug($process->slug);

        if (is_file($path)) {
            unlink($path);
        }
    }

    public function exists(string $idOrSlug): bool
    {
        return $this->find($idOrSlug) !== null;
    }

    private function pathForSlug(string $slug): string
    {
        return SafePath::resolveWithin($this->directory, $slug.'.json');
    }

    private function hydrateFile(string $file): ProcessDefinition
    {
        $contents = file_get_contents($file);

        if ($contents === false) {
            throw InvalidProcessDefinitionException::withErrors(["Unable to read process definition file [{$file}]."]);
        }

        /** @var mixed $payload */
        $payload = json_decode($contents, true);

        if (! is_array($payload)) {
            throw InvalidProcessDefinitionException::withErrors(["Process definition file [{$file}] does not contain valid JSON."]);
        }

        return ProcessDefinition::fromArray($payload);
    }

    private function ensureDirectoryExists(): void
    {
        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }
}
