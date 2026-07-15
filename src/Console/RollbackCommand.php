<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Console;

use Illuminate\Console\Command;
use MohamedZaki\LaravelProcessBuilder\Audit\AuditLogger;
use MohamedZaki\LaravelProcessBuilder\Backup\BackupService;
use MohamedZaki\LaravelProcessBuilder\Contracts\ManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessBuilderException;

final class RollbackCommand extends Command
{
    protected $signature = 'process-builder:rollback {process : The process slug or id} {backup : The backup id to restore}';

    protected $description = 'Roll back a process to a previous backup';

    public function handle(
        ProcessRepository $repository,
        BackupService $backupService,
        ManifestRepository $manifestRepository,
        AuditLogger $auditLogger,
    ): int {
        /** @var string $identifier */
        $identifier = $this->argument('process');

        /** @var string $backupId */
        $backupId = $this->argument('backup');

        $process = $repository->find($identifier);

        if ($process === null) {
            $this->components->error("No process found matching [{$identifier}].");

            return self::FAILURE;
        }

        try {
            $backupMetadata = $backupService->findBackup($process->slug, $backupId);
        } catch (ProcessBuilderException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $manifest = $manifestRepository->find($process->slug);

        if ($manifest === null) {
            $this->components->error("No generation manifest found for process [{$process->slug}]; cannot determine file locations to restore.");

            return self::FAILURE;
        }

        if (! $this->components->confirm("Roll back [{$process->slug}] to backup [{$backupId}]?")) {
            $this->components->warn('Rollback cancelled.');

            return self::FAILURE;
        }

        $auditLogger->record(AuditAction::RollbackStarted, 'started', $process->id, $process->version);

        try {
            $currentPaths = [];

            foreach ($manifest->generatedFiles as $entry) {
                $currentPaths[$entry->relativePath] = $entry->absolutePath;
            }

            $preRollbackBackup = $backupService->createBackup($process->slug, $currentPaths);

            $auditLogger->record(
                AuditAction::BackupCreated,
                'success',
                $process->id,
                $process->version,
                $preRollbackBackup->backedUpRelativePaths,
            );

            $backupService->restore($process->slug, $backupId, $currentPaths);
        } catch (ProcessBuilderException $exception) {
            $auditLogger->record(AuditAction::RollbackFailed, 'failure', $process->id, $process->version);

            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $auditLogger->record(
            AuditAction::RollbackCompleted,
            'success',
            $process->id,
            $process->version,
            $backupMetadata->backedUpRelativePaths,
        );

        $this->components->info('Rollback complete: '.count($backupMetadata->backedUpRelativePaths).' file(s) restored.');

        return self::SUCCESS;
    }
}
