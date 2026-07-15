<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use MohamedZaki\LaravelProcessBuilder\Audit\AuditLogger;
use MohamedZaki\LaravelProcessBuilder\Backup\BackupService;
use MohamedZaki\LaravelProcessBuilder\Contracts\ManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessBuilderException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessNotFoundException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\RollbackFailedException;

final class ProcessRollbackController
{
    public function __construct(
        private readonly ProcessRepository $repository,
        private readonly BackupService $backupService,
        private readonly ManifestRepository $manifestRepository,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function __invoke(string $process, string $backup): JsonResponse
    {
        $definition = $this->repository->find($process);

        if ($definition === null) {
            throw ProcessNotFoundException::forIdentifier($process);
        }

        $backupMetadata = $this->backupService->findBackup($definition->slug, $backup);

        $manifest = $this->manifestRepository->find($definition->slug);

        if ($manifest === null) {
            throw new RollbackFailedException("No generation manifest found for process [{$definition->slug}]; cannot determine file locations to restore.");
        }

        $this->auditLogger->record(AuditAction::RollbackStarted, 'started', $definition->id, $definition->version);

        try {
            // The manifest records the exact absolute path used at generation time, which may live
            // outside base_path() when process-builder.output.* config points elsewhere.
            $currentPaths = [];

            foreach ($manifest->generatedFiles as $entry) {
                $currentPaths[$entry->relativePath] = $entry->absolutePath;
            }

            // Back up the current state before restoring, so the rollback itself is reversible.
            $preRollbackBackup = $this->backupService->createBackup($definition->slug, $currentPaths);

            $this->auditLogger->record(
                AuditAction::BackupCreated,
                'success',
                $definition->id,
                $definition->version,
                $preRollbackBackup->backedUpRelativePaths,
            );

            $this->backupService->restore($definition->slug, $backup, $currentPaths);
        } catch (ProcessBuilderException $exception) {
            $this->auditLogger->record(AuditAction::RollbackFailed, 'failure', $definition->id, $definition->version);

            throw $exception;
        }

        $this->auditLogger->record(
            AuditAction::RollbackCompleted,
            'success',
            $definition->id,
            $definition->version,
            $backupMetadata->backedUpRelativePaths,
        );

        return response()->json([
            'data' => [
                'restoredBackupId' => $backupMetadata->id,
                'restoredFiles' => $backupMetadata->backedUpRelativePaths,
            ],
            'meta' => [],
            'errors' => [],
        ]);
    }
}
