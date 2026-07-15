<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Backup;

use DateTimeImmutable;
use MohamedZaki\LaravelProcessBuilder\DTO\BackupMetadata;
use MohamedZaki\LaravelProcessBuilder\Exceptions\BackupNotFoundException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\RollbackFailedException;
use MohamedZaki\LaravelProcessBuilder\Filesystem\SafePath;

final class BackupService
{
    public function __construct(
        private readonly string $backupsRootDirectory,
        private readonly int $retention = 20,
    ) {
    }

    /**
     * Back up the current contents of the given absolute file paths (only those that exist).
     *
     * @param  array<string, string>  $absolutePathsByRelativePath  relativePath => absolutePath
     */
    public function createBackup(string $processSlug, array $absolutePathsByRelativePath): BackupMetadata
    {
        SafePath::assertSafeSlug($processSlug);

        $backupId = (new DateTimeImmutable())->format('Ymd_His').'_'.bin2hex(random_bytes(4));
        $backupDirectory = $this->processDirectory($processSlug).DIRECTORY_SEPARATOR.$backupId;

        mkdir($backupDirectory, 0755, true);

        $backedUp = [];

        foreach ($absolutePathsByRelativePath as $relativePath => $absolutePath) {
            if (! is_file($absolutePath)) {
                continue;
            }

            $destination = $backupDirectory.DIRECTORY_SEPARATOR.$this->encodeRelativePath($relativePath);

            copy($absolutePath, $destination);

            $backedUp[] = $relativePath;
        }

        $metadata = new BackupMetadata(
            id: $backupId,
            processSlug: $processSlug,
            createdAt: (new DateTimeImmutable())->format(DATE_ATOM),
            backedUpRelativePaths: $backedUp,
        );

        file_put_contents(
            $backupDirectory.DIRECTORY_SEPARATOR.'metadata.json',
            json_encode($metadata->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        $this->enforceRetention($processSlug);

        return $metadata;
    }

    /**
     * @return list<BackupMetadata>
     */
    public function listBackups(string $processSlug): array
    {
        SafePath::assertSafeSlug($processSlug);

        $directory = $this->processDirectory($processSlug);

        if (! is_dir($directory)) {
            return [];
        }

        $backups = [];

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $metadata = $this->readMetadata($processSlug, $entry);

            if ($metadata !== null) {
                $backups[] = $metadata;
            }
        }

        usort($backups, static fn (BackupMetadata $a, BackupMetadata $b): int => $b->createdAt <=> $a->createdAt);

        return $backups;
    }

    public function findBackup(string $processSlug, string $backupId): BackupMetadata
    {
        $metadata = $this->readMetadata($processSlug, $backupId);

        if ($metadata === null) {
            throw BackupNotFoundException::forId($backupId);
        }

        return $metadata;
    }

    /**
     * Restore backed-up files to their original absolute locations.
     *
     * @param  array<string, string>  $absolutePathsByRelativePath  relativePath => absolutePath, for every path the backup may contain
     */
    public function restore(string $processSlug, string $backupId, array $absolutePathsByRelativePath): void
    {
        $metadata = $this->findBackup($processSlug, $backupId);
        $backupDirectory = $this->processDirectory($processSlug).DIRECTORY_SEPARATOR.$backupId;

        foreach ($metadata->backedUpRelativePaths as $relativePath) {
            $source = $backupDirectory.DIRECTORY_SEPARATOR.$this->encodeRelativePath($relativePath);
            $destination = $absolutePathsByRelativePath[$relativePath] ?? null;

            if ($destination === null || ! is_file($source)) {
                throw new RollbackFailedException("Unable to restore [{$relativePath}]: source or destination path unknown.");
            }

            $directory = dirname($destination);

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            if (! copy($source, $destination)) {
                throw new RollbackFailedException("Failed to restore [{$relativePath}] from backup [{$backupId}].");
            }
        }
    }

    private function readMetadata(string $processSlug, string $backupId): ?BackupMetadata
    {
        $path = $this->processDirectory($processSlug).DIRECTORY_SEPARATOR.$backupId.DIRECTORY_SEPARATOR.'metadata.json';

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        /** @var mixed $payload */
        $payload = json_decode($contents, true);

        return is_array($payload) ? BackupMetadata::fromArray($payload) : null;
    }

    private function enforceRetention(string $processSlug): void
    {
        $backups = $this->listBackups($processSlug);

        if (count($backups) <= $this->retention) {
            return;
        }

        $toDelete = array_slice($backups, $this->retention);

        foreach ($toDelete as $backup) {
            $this->deleteBackupDirectory($processSlug, $backup->id);
        }
    }

    private function deleteBackupDirectory(string $processSlug, string $backupId): void
    {
        $directory = $this->processDirectory($processSlug).DIRECTORY_SEPARATOR.$backupId;

        foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($directory);
    }

    private function processDirectory(string $processSlug): string
    {
        return $this->backupsRootDirectory.DIRECTORY_SEPARATOR.$processSlug;
    }

    private function encodeRelativePath(string $relativePath): string
    {
        return (string) preg_replace('/[\/\\\\:]/', '__', $relativePath);
    }
}
