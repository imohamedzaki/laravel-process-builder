<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Domain\Processes;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, ProcessDefinition>
 */
final class ProcessCollection implements IteratorAggregate
{
    /**
     * @param  list<ProcessDefinition>  $items
     */
    public function __construct(private readonly array $items)
    {
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return list<ProcessDefinition>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(static fn (ProcessDefinition $process): array => $process->toArray(), $this->items);
    }
}
