<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Console;

use Illuminate\Console\Command;
use MohamedZaki\LaravelProcessBuilder\Audit\AuditLogger;
use MohamedZaki\LaravelProcessBuilder\Compilation\ProcessCompiler;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\DTO\GeneratedFile;
use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessBuilderException;
use MohamedZaki\LaravelProcessBuilder\Generation\GenerationService;
use MohamedZaki\LaravelProcessBuilder\Security\PreviewTokenSigner;

final class GenerateCommand extends Command
{
    protected $signature = 'process-builder:generate
        {process : The process slug or id}
        {--preview : Compile and display the files that would be generated without writing anything}
        {--force : Overwrite hand-edited managed files}
        {--no-backup : Skip creating a backup before overwriting existing files}';

    protected $description = 'Generate real Laravel code from a process definition';

    public function handle(
        ProcessRepository $repository,
        ProcessCompiler $compiler,
        PreviewTokenSigner $tokenSigner,
        GenerationService $generationService,
        AuditLogger $auditLogger,
    ): int {
        /** @var string $identifier */
        $identifier = $this->argument('process');

        $process = $repository->find($identifier);

        if ($process === null) {
            $this->components->error("No process found matching [{$identifier}].");

            return self::FAILURE;
        }

        $compilation = $compiler->compile($process);

        if (! $compilation->isSuccessful()) {
            $this->components->error('Process is invalid; cannot generate.');

            foreach ($compilation->validation->errors() as $error) {
                $this->components->bulletList(["[{$error->code}] {$error->message}"]);
            }

            return self::FAILURE;
        }

        foreach ($compilation->files->all() as $file) {
            $this->components->twoColumnDetail($file->relativePath, "<fg=gray>{$file->logicalType}</>");
        }

        if ($this->option('preview')) {
            return self::SUCCESS;
        }

        if (! $this->confirmUnlessTrustedNonInteractive()) {
            $this->components->warn('Generation cancelled.');

            return self::FAILURE;
        }

        $definitionChecksum = hash('sha256', json_encode($process->toArray(), JSON_THROW_ON_ERROR));
        $token = $tokenSigner->sign($process->id, $process->version, $definitionChecksum);

        $auditLogger->record(AuditAction::GenerationStarted, 'started', $process->id, $process->version);

        try {
            $result = $generationService->generate(
                $process,
                $tokenSigner->verify($token),
                (bool) $this->option('force'),
                ! (bool) $this->option('no-backup'),
            );
        } catch (ProcessBuilderException $exception) {
            $auditLogger->record(AuditAction::GenerationFailed, 'failure', $process->id, $process->version);

            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result->backup !== null) {
            $auditLogger->record(
                AuditAction::BackupCreated,
                'success',
                $process->id,
                $process->version,
                $result->backup->backedUpRelativePaths,
            );
        }

        $auditLogger->record(
            AuditAction::GenerationCompleted,
            'success',
            $process->id,
            $process->version,
            array_map(static fn (GeneratedFile $file): string => $file->relativePath, $result->files->all()),
        );

        $this->components->info('Generation complete: '.$result->files->count().' file(s) written.');

        return self::SUCCESS;
    }

    private function confirmUnlessTrustedNonInteractive(): bool
    {
        if ($this->option('no-interaction')) {
            return $this->getLaravel()->environment('local', 'testing', 'development');
        }

        return $this->components->confirm('This will write files to your application. Continue?');
    }
}
