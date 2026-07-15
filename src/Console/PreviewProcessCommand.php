<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Console;

use Illuminate\Console\Command;
use MohamedZaki\LaravelProcessBuilder\Audit\AuditLogger;
use MohamedZaki\LaravelProcessBuilder\Compilation\ProcessCompiler;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\DTO\GeneratedFile;
use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;
use MohamedZaki\LaravelProcessBuilder\Security\PreviewTokenSigner;

final class PreviewProcessCommand extends Command
{
    protected $signature = 'process-builder:preview {process : The process slug or id}';

    protected $description = 'Compile a process in memory and display the generated files without writing to disk';

    public function handle(
        ProcessRepository $repository,
        ProcessCompiler $compiler,
        PreviewTokenSigner $tokenSigner,
        AuditLogger $auditLogger,
    ): int {
        /** @var string $identifier */
        $identifier = $this->argument('process');

        $process = $repository->find($identifier);

        if ($process === null) {
            $this->components->error("No process found matching [{$identifier}].");

            return self::FAILURE;
        }

        $result = $compiler->compile($process);

        $auditLogger->record(
            AuditAction::PreviewCreated,
            $result->isSuccessful() ? 'success' : 'failure',
            $process->id,
            $process->version,
            array_map(static fn (GeneratedFile $file): string => $file->relativePath, $result->files->all()),
        );

        if (! $result->isSuccessful()) {
            $this->components->error('Process is invalid; nothing to preview.');

            foreach ($result->validation->errors() as $error) {
                $this->components->bulletList(["[{$error->code}] {$error->message}"]);
            }

            return self::FAILURE;
        }

        foreach ($result->files->all() as $file) {
            $this->components->twoColumnDetail($file->relativePath, "<fg=gray>{$file->logicalType}</>");
        }

        $definitionChecksum = hash('sha256', json_encode($process->toArray(), JSON_THROW_ON_ERROR));
        $token = $tokenSigner->sign($process->id, $process->version, $definitionChecksum);

        $this->newLine();
        $this->components->info('Preview token (use with process-builder:generate --token=):');
        $this->line($token);

        return self::SUCCESS;
    }
}
