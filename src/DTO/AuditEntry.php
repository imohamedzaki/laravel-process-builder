<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;

final class AuditEntry
{
    /**
     * @param  list<string>  $changedFiles
     */
    public function __construct(
        public readonly string $timestamp,
        public readonly ?string $userId,
        public readonly ?string $processId,
        public readonly ?int $processVersion,
        public readonly AuditAction $action,
        public readonly string $result,
        public readonly array $changedFiles,
        public readonly string $correlationId,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'userId' => $this->userId,
            'processId' => $this->processId,
            'processVersion' => $this->processVersion,
            'action' => $this->action->value,
            'result' => $this->result,
            'changedFiles' => $this->changedFiles,
            'correlationId' => $this->correlationId,
        ];
    }
}
