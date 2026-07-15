<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use MohamedZaki\LaravelProcessBuilder\Audit\AuditLogger;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessNotFoundException;
use MohamedZaki\LaravelProcessBuilder\Validation\ValidationPipeline;

final class ProcessValidationController
{
    public function __construct(
        private readonly ProcessRepository $repository,
        private readonly ValidationPipeline $pipeline,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function __invoke(string $process): JsonResponse
    {
        $definition = $this->repository->find($process);

        if ($definition === null) {
            throw ProcessNotFoundException::forIdentifier($process);
        }

        $result = $this->pipeline->validate($definition);

        $this->auditLogger->record(
            AuditAction::ProcessValidated,
            $result->isValid() ? 'success' : 'failure',
            $definition->id,
            $definition->version,
        );

        return response()->json([
            'data' => $result->toArray(),
            'meta' => [],
            'errors' => [],
        ]);
    }
}
