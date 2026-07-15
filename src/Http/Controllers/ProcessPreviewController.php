<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use MohamedZaki\LaravelProcessBuilder\Audit\AuditLogger;
use MohamedZaki\LaravelProcessBuilder\Compilation\ProcessCompiler;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\DTO\GeneratedFile;
use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessNotFoundException;
use MohamedZaki\LaravelProcessBuilder\Security\PreviewTokenSigner;

final class ProcessPreviewController
{
    public function __construct(
        private readonly ProcessRepository $repository,
        private readonly ProcessCompiler $compiler,
        private readonly PreviewTokenSigner $tokenSigner,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function __invoke(string $process): JsonResponse
    {
        $definition = $this->repository->find($process);

        if ($definition === null) {
            throw ProcessNotFoundException::forIdentifier($process);
        }

        $result = $this->compiler->compile($definition);

        $definitionChecksum = hash('sha256', json_encode($definition->toArray(), JSON_THROW_ON_ERROR));

        $token = $result->isSuccessful()
            ? $this->tokenSigner->sign($definition->id, $definition->version, $definitionChecksum)
            : null;

        $this->auditLogger->record(
            AuditAction::PreviewCreated,
            $result->isSuccessful() ? 'success' : 'failure',
            $definition->id,
            $definition->version,
            array_map(
                static fn (GeneratedFile $file): string => $file->relativePath,
                $result->files->all(),
            ),
        );

        return response()->json([
            'data' => [
                ...$result->toArray(),
                'previewToken' => $token,
            ],
            'meta' => [],
            'errors' => [],
        ]);
    }
}
