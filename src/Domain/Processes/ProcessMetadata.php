<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Domain\Processes;

use DateTimeImmutable;

final class ProcessMetadata
{
    public function __construct(
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
        public readonly ?DateTimeImmutable $generatedAt,
        public readonly ?string $generatorVersion,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $now = new DateTimeImmutable();

        return new self(
            createdAt: self::parseDate($payload['createdAt'] ?? null) ?? $now,
            updatedAt: self::parseDate($payload['updatedAt'] ?? null) ?? $now,
            generatedAt: self::parseDate($payload['generatedAt'] ?? null),
            generatorVersion: isset($payload['generatorVersion']) && is_string($payload['generatorVersion'])
                ? $payload['generatorVersion']
                : null,
        );
    }

    public function withUpdatedNow(): self
    {
        return new self($this->createdAt, new DateTimeImmutable(), $this->generatedAt, $this->generatorVersion);
    }

    public function withGenerated(DateTimeImmutable $generatedAt, string $generatorVersion): self
    {
        return new self($this->createdAt, $this->updatedAt, $generatedAt, $generatorVersion);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
            'generatedAt' => $this->generatedAt?->format(DATE_ATOM),
            'generatorVersion' => $this->generatorVersion,
        ];
    }

    private static function parseDate(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(DATE_ATOM, $value);

        return $date === false ? null : $date;
    }
}
