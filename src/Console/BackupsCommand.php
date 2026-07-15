<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Console;

use Illuminate\Console\Command;
use MohamedZaki\LaravelProcessBuilder\Backup\BackupService;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\DTO\BackupMetadata;

final class BackupsCommand extends Command
{
    protected $signature = 'process-builder:backups {process : The process slug or id}';

    protected $description = 'List the available backups for a process';

    public function handle(ProcessRepository $repository, BackupService $backupService): int
    {
        /** @var string $identifier */
        $identifier = $this->argument('process');

        $process = $repository->find($identifier);

        if ($process === null) {
            $this->components->error("No process found matching [{$identifier}].");

            return self::FAILURE;
        }

        $backups = $backupService->listBackups($process->slug);

        if ($backups === []) {
            $this->components->info('No backups found for this process.');

            return self::SUCCESS;
        }

        $this->table(
            ['Backup Id', 'Created At', 'Files'],
            array_map(
                static fn (BackupMetadata $backup): array => [
                    $backup->id,
                    $backup->createdAt,
                    (string) count($backup->backedUpRelativePaths),
                ],
                $backups,
            ),
        );

        return self::SUCCESS;
    }
}
