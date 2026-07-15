<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Generation;

final class GenerationLock
{
    /** @var resource|null */
    private $handle;

    public function __construct(private readonly string $lockDirectory, private readonly string $processSlug)
    {
    }

    public function acquire(): void
    {
        if (! is_dir($this->lockDirectory)) {
            mkdir($this->lockDirectory, 0755, true);
        }

        $path = $this->lockDirectory.DIRECTORY_SEPARATOR.$this->processSlug.'.lock';

        $handle = fopen($path, 'c');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open lock file [{$path}].");
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            throw new \RuntimeException("Another generation is already in progress for process [{$this->processSlug}].");
        }

        $this->handle = $handle;
    }

    public function release(): void
    {
        if ($this->handle !== null) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
            $this->handle = null;
        }
    }
}
