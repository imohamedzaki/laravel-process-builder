<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Contracts;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessCollection;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessNotFoundException;

interface ProcessRepository
{
    public function all(): ProcessCollection;

    /**
     * @throws ProcessNotFoundException
     */
    public function find(string $idOrSlug): ?ProcessDefinition;

    public function save(ProcessDefinition $process): void;

    public function delete(string $idOrSlug): void;

    public function exists(string $idOrSlug): bool;
}
