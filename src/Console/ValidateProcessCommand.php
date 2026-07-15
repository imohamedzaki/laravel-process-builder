<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Console;

use Illuminate\Console\Command;
use MohamedZaki\LaravelProcessBuilder\Audit\AuditLogger;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationError;
use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;
use MohamedZaki\LaravelProcessBuilder\Validation\ValidationPipeline;

final class ValidateProcessCommand extends Command
{
    protected $signature = 'process-builder:validate {process? : The process slug or id; validates all processes when omitted}';

    protected $description = 'Validate one or all process definitions';

    public function handle(ProcessRepository $repository, ValidationPipeline $pipeline, AuditLogger $auditLogger): int
    {
        /** @var string|null $identifier */
        $identifier = $this->argument('process');

        if ($identifier !== null) {
            $process = $repository->find($identifier);

            if ($process === null) {
                $this->components->error("No process found matching [{$identifier}].");

                return self::FAILURE;
            }

            $processes = [$process];
        } else {
            $processes = $repository->all()->all();
        }

        $allValid = true;

        foreach ($processes as $process) {
            $allValid = $this->validateOne($process, $pipeline, $auditLogger) && $allValid;
        }

        return $allValid ? self::SUCCESS : self::FAILURE;
    }

    private function validateOne(ProcessDefinition $process, ValidationPipeline $pipeline, AuditLogger $auditLogger): bool
    {
        $result = $pipeline->validate($process);

        $auditLogger->record(
            AuditAction::ProcessValidated,
            $result->isValid() ? 'success' : 'failure',
            $process->id,
            $process->version,
        );

        if ($result->isValid()) {
            $this->components->task("Validate [{$process->slug}]", fn (): bool => true);

            return true;
        }

        $this->components->task("Validate [{$process->slug}]", fn (): bool => false);

        foreach ($result->errors() as $error) {
            $this->components->bulletList([$this->formatIssue($error)]);
        }

        return false;
    }

    private function formatIssue(ValidationError $issue): string
    {
        $location = $issue->nodeId !== null ? " (node: {$issue->nodeId})" : '';

        return "[{$issue->code}] {$issue->message}{$location}";
    }
}
