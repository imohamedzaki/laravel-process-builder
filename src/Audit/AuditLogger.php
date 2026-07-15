<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Audit;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use MohamedZaki\LaravelProcessBuilder\DTO\AuditEntry;
use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;

final class AuditLogger
{
    public function __construct(
        private readonly string $logPath,
        private readonly ?AuthFactory $auth = null,
    ) {
    }

    /**
     * @param  list<string>  $changedFiles
     */
    public function record(
        AuditAction $action,
        string $result,
        ?string $processId = null,
        ?int $processVersion = null,
        array $changedFiles = [],
        ?string $correlationId = null,
    ): AuditEntry {
        $entry = new AuditEntry(
            timestamp: gmdate('Y-m-d\TH:i:s\Z'),
            userId: $this->currentUserId(),
            processId: $processId,
            processVersion: $processVersion,
            action: $action,
            result: $result,
            changedFiles: $changedFiles,
            correlationId: $correlationId ?? bin2hex(random_bytes(8)),
        );

        $this->write($entry);

        return $entry;
    }

    private function write(AuditEntry $entry): void
    {
        $directory = dirname($this->logPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $line = json_encode($entry->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;

        $handle = fopen($this->logPath, 'a');

        if ($handle === false) {
            return;
        }

        try {
            flock($handle, LOCK_EX);
            fwrite($handle, $line);
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    private function currentUserId(): ?string
    {
        if ($this->auth === null) {
            return null;
        }

        try {
            $user = $this->auth->guard()->user();
        } catch (\InvalidArgumentException) {
            // No default auth guard is configured for this application; audit entries
            // are still written, just without a resolvable user identifier.
            return null;
        }

        if ($user === null) {
            return null;
        }

        $identifier = $user->getAuthIdentifier();

        return $identifier === null ? null : (string) $identifier;
    }
}
