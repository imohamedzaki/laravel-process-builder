<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

final class GenerationManifest
{
    /**
     * @param  list<ManifestEntry>  $generatedFiles
     */
    public function __construct(
        public readonly string $processId,
        public readonly int $processVersion,
        public readonly array $generatedFiles,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        /** @var list<array<string, mixed>> $entries */
        $entries = is_array($payload['generatedFiles'] ?? null) ? array_values($payload['generatedFiles']) : [];

        return new self(
            processId: (string) ($payload['processId'] ?? ''),
            processVersion: (int) ($payload['processVersion'] ?? 0),
            generatedFiles: array_map(ManifestEntry::fromArray(...), $entries),
        );
    }

    public function entryForRelativePath(string $relativePath): ?ManifestEntry
    {
        foreach ($this->generatedFiles as $entry) {
            if ($entry->relativePath === $relativePath) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'processId' => $this->processId,
            'processVersion' => $this->processVersion,
            'generatedFiles' => array_map(static fn (ManifestEntry $entry): array => $entry->toArray(), $this->generatedFiles),
        ];
    }
}
