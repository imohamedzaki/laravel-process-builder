<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

use MohamedZaki\LaravelProcessBuilder\Enums\ValidationSeverity;

final class ValidationResult
{
    /**
     * @param  list<ValidationError>  $issues
     */
    public function __construct(public readonly array $issues)
    {
    }

    public static function valid(): self
    {
        return new self([]);
    }

    /**
     * @param  list<ValidationError>  $issues
     */
    public static function fromIssues(array $issues): self
    {
        return new self($issues);
    }

    public function isValid(): bool
    {
        return $this->errors() === [];
    }

    /**
     * @return list<ValidationError>
     */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn (ValidationError $issue): bool => $issue->severity === ValidationSeverity::Error,
        ));
    }

    /**
     * @return list<ValidationError>
     */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn (ValidationError $issue): bool => $issue->severity === ValidationSeverity::Warning,
        ));
    }

    public function merge(self $other): self
    {
        return new self([...$this->issues, ...$other->issues]);
    }

    /**
     * @return array{valid: bool, errors: list<array<string, mixed>>, warnings: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->isValid(),
            'errors' => array_map(static fn (ValidationError $error): array => $error->toArray(), $this->errors()),
            'warnings' => array_map(static fn (ValidationError $warning): array => $warning->toArray(), $this->warnings()),
        ];
    }
}
