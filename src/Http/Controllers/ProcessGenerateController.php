<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use MohamedZaki\LaravelProcessBuilder\Audit\AuditLogger;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\DTO\GeneratedFile;
use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessBuilderException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessNotFoundException;
use MohamedZaki\LaravelProcessBuilder\Generation\GenerationService;
use MohamedZaki\LaravelProcessBuilder\Http\Requests\GenerateProcessRequest;
use MohamedZaki\LaravelProcessBuilder\LaravelProcessBuilderServiceProvider;
use MohamedZaki\LaravelProcessBuilder\Security\PreviewTokenSigner;

final class ProcessGenerateController
{
    public function __construct(
        private readonly ProcessRepository $repository,
        private readonly GenerationService $generationService,
        private readonly PreviewTokenSigner $tokenSigner,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function __invoke(GenerateProcessRequest $request, string $process): JsonResponse
    {
        $definition = $this->repository->find($process);

        if ($definition === null) {
            throw ProcessNotFoundException::forIdentifier($process);
        }

        $token = $this->tokenSigner->verify($request->validated('previewToken'));

        $this->auditLogger->record(AuditAction::GenerationStarted, 'started', $definition->id, $definition->version);

        try {
            $result = $this->generationService->generate(
                $definition,
                $token,
                (bool) $request->validated('force', false),
            );
        } catch (ProcessBuilderException $exception) {
            $this->auditLogger->record(AuditAction::GenerationFailed, 'failure', $definition->id, $definition->version);

            throw $exception;
        }

        if ($result->backup !== null) {
            $this->auditLogger->record(
                AuditAction::BackupCreated,
                'success',
                $definition->id,
                $definition->version,
                $result->backup->backedUpRelativePaths,
            );
        }

        $generatedDefinition = $definition->withGenerated(LaravelProcessBuilderServiceProvider::VERSION);
        $this->repository->save($generatedDefinition);

        $this->auditLogger->record(
            AuditAction::GenerationCompleted,
            'success',
            $definition->id,
            $definition->version,
            array_map(
                static fn (GeneratedFile $file): string => $file->relativePath,
                $result->files->all(),
            ),
        );

        return response()->json([
            'data' => $result->toArray(),
            'meta' => [],
            'errors' => [],
        ]);
    }
}
