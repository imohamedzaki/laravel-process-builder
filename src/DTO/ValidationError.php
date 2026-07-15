<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

use MohamedZaki\LaravelProcessBuilder\Enums\ValidationSeverity;

final class ValidationError
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
        public readonly ?string $nodeId,
        public readonly ?string $field,
        public readonly ValidationSeverity $severity,
    ) {
    }

    public static function error(string $code, string $message, ?string $nodeId = null, ?string $field = null): self
    {
        return new self($code, $message, $nodeId, $field, ValidationSeverity::Error);
    }

    public static function warning(string $code, string $message, ?string $nodeId = null, ?string $field = null): self
    {
        return new self($code, $message, $nodeId, $field, ValidationSeverity::Warning);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'nodeId' => $this->nodeId,
            'field' => $this->field,
            'severity' => $this->severity->value,
        ];
    }
}
