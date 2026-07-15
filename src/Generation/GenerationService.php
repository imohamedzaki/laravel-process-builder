<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Generation;

use MohamedZaki\LaravelProcessBuilder\Backup\BackupService;
use MohamedZaki\LaravelProcessBuilder\Compilation\ProcessCompiler;
use MohamedZaki\LaravelProcessBuilder\Contracts\ManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\GeneratedFile;
use MohamedZaki\LaravelProcessBuilder\DTO\GeneratedFileCollection;
use MohamedZaki\LaravelProcessBuilder\DTO\GenerationManifest;
use MohamedZaki\LaravelProcessBuilder\DTO\GenerationResult;
use MohamedZaki\LaravelProcessBuilder\DTO\ManifestEntry;
use MohamedZaki\LaravelProcessBuilder\Exceptions\FileOwnershipConflictException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\GenerationConflictException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\GenerationDisabledException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidProcessDefinitionException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\UnauthorizedEnvironmentException;
use MohamedZaki\LaravelProcessBuilder\Filesystem\AtomicFileWriter;
use MohamedZaki\LaravelProcessBuilder\Security\PreviewToken;

final class GenerationService
{
    /**
     * @param  list<string>  $allowedEnvironments
     */
    public function __construct(
        private readonly ProcessCompiler $compiler,
        private readonly AtomicFileWriter $writer,
        private readonly BackupService $backupService,
        private readonly ManifestRepository $manifestRepository,
        private readonly string $lockDirectory,
        private readonly bool $generationEnabled,
        private readonly array $allowedEnvironments,
        private readonly string $currentEnvironment,
        private readonly bool $createBackups,
        private readonly bool $validateSyntax,
    ) {
    }

    public function generate(ProcessDefinition $process, PreviewToken $token, bool $force = false, ?bool $createBackups = null): GenerationResult
    {
        if (! $this->generationEnabled) {
            throw GenerationDisabledException::create();
        }

        if ($this->allowedEnvironments !== [] && ! in_array($this->currentEnvironment, $this->allowedEnvironments, strict: true)) {
            throw UnauthorizedEnvironmentException::forEnvironment($this->currentEnvironment);
        }

        if ($token->processId !== $process->id || $token->processVersion !== $process->version) {
            throw GenerationConflictException::definitionChangedSincePreview();
        }

        $definitionChecksum = hash('sha256', json_encode($process->toArray(), JSON_THROW_ON_ERROR));

        if (! hash_equals($token->definitionChecksum, $definitionChecksum)) {
            throw GenerationConflictException::definitionChangedSincePreview();
        }

        $compilation = $this->compiler->compile($process);

        if (! $compilation->isSuccessful()) {
            throw InvalidProcessDefinitionException::withErrors(
                array_map(static fn ($error): string => $error->message, $compilation->validation->errors()),
            );
        }

        $lock = new GenerationLock($this->lockDirectory, $process->slug);
        $lock->acquire();

        try {
            return $this->writeFiles($process, $compilation->files->all(), $force, $createBackups ?? $this->createBackups);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  list<GeneratedFile>  $files
     */
    private function writeFiles(ProcessDefinition $process, array $files, bool $force, bool $createBackups): GenerationResult
    {
        $existingManifest = $this->manifestRepository->find($process->slug);

        $this->assertNoUnauthorizedOverwrites($files, $existingManifest, $force);

        $backup = null;

        if ($createBackups) {
            $absolutePathsByRelativePath = [];

            foreach ($files as $file) {
                $absolutePathsByRelativePath[$file->relativePath] = $file->absolutePath;
            }

            $backup = $this->backupService->createBackup($process->slug, $absolutePathsByRelativePath);
        }

        foreach ($files as $file) {
            $this->writer->write($file->absolutePath, $file->contents, $this->validateSyntax);
        }

        $manifest = new GenerationManifest(
            processId: $process->id,
            processVersion: $process->version,
            generatedFiles: array_map(
                static fn (GeneratedFile $file): ManifestEntry => new ManifestEntry(
                    logicalType: $file->logicalType,
                    relativePath: $file->relativePath,
                    absolutePath: $file->absolutePath,
                    sha256: $file->checksum(),
                ),
                $files,
            ),
        );

        $this->manifestRepository->save($process->slug, $manifest);

        return new GenerationResult(new GeneratedFileCollection($files), $backup);
    }

    /**
     * @param  list<GeneratedFile>  $files
     */
    private function assertNoUnauthorizedOverwrites(array $files, ?GenerationManifest $existingManifest, bool $force): void
    {
        if ($force) {
            return;
        }

        foreach ($files as $file) {
            if (! is_file($file->absolutePath)) {
                continue;
            }

            $existingEntry = $existingManifest?->entryForRelativePath($file->relativePath);

            if ($existingEntry === null) {
                // File exists on disk but was never generated/tracked by this process — never touch it.
                throw FileOwnershipConflictException::forPath($file->relativePath);
            }

            $currentContents = file_get_contents($file->absolutePath);
            $currentChecksum = $currentContents === false ? null : hash('sha256', $currentContents);

            if ($currentChecksum !== $existingEntry->sha256) {
                // The managed file was modified outside the tool since the last generation.
                throw GenerationConflictException::managedFileModifiedManually($file->relativePath);
            }
        }
    }
}
