<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use MohamedZaki\LaravelProcessBuilder\Backup\BackupService;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\DTO\BackupMetadata;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessNotFoundException;

final class ProcessBackupsController
{
    public function __construct(
        private readonly ProcessRepository $repository,
        private readonly BackupService $backupService,
    ) {
    }

    public function __invoke(string $process): JsonResponse
    {
        $definition = $this->repository->find($process);

        if ($definition === null) {
            throw ProcessNotFoundException::forIdentifier($process);
        }

        $backups = $this->backupService->listBackups($definition->slug);

        return response()->json([
            'data' => array_map(static fn (BackupMetadata $backup): array => $backup->toArray(), $backups),
            'meta' => ['count' => count($backups)],
            'errors' => [],
        ]);
    }
}
