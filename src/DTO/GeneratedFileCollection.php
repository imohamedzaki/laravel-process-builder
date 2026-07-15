<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, GeneratedFile>
 */
final class GeneratedFileCollection implements IteratorAggregate
{
    /**
     * @param  list<GeneratedFile>  $files
     */
    public function __construct(private readonly array $files)
    {
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->files);
    }

    public function count(): int
    {
        return count($this->files);
    }

    /**
     * @return list<GeneratedFile>
     */
    public function all(): array
    {
        return $this->files;
    }

    public function merge(self $other): self
    {
        return new self([...$this->files, ...$other->files]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(static fn (GeneratedFile $file): array => $file->toArray(), $this->files);
    }
}
