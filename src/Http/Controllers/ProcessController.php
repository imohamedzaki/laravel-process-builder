<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use MohamedZaki\LaravelProcessBuilder\Audit\AuditLogger;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Enums\AuditAction;
use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidProcessDefinitionException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessNotFoundException;
use MohamedZaki\LaravelProcessBuilder\Http\Requests\StoreProcessRequest;
use MohamedZaki\LaravelProcessBuilder\Http\Requests\UpdateProcessRequest;

final class ProcessController
{
    public function __construct(
        private readonly ProcessRepository $repository,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function index(): JsonResponse
    {
        $processes = $this->repository->all();

        return response()->json([
            'data' => $processes->toArray(),
            'meta' => ['count' => $processes->count()],
            'errors' => [],
        ]);
    }

    public function store(StoreProcessRequest $request): JsonResponse
    {
        if ($this->repository->exists($request->validated('slug'))) {
            return response()->json([
                'data' => null,
                'meta' => [],
                'errors' => [['code' => 'process.slug_taken', 'message' => 'A process with this slug already exists.']],
            ], 409);
        }

        try {
            $process = ProcessDefinition::fromArray($request->validated());
        } catch (InvalidProcessDefinitionException $exception) {
            return $this->invalidDefinitionResponse($exception);
        }

        $this->repository->save($process);

        $this->auditLogger->record(AuditAction::ProcessCreated, 'success', $process->id, $process->version);

        return response()->json([
            'data' => $process->toArray(),
            'meta' => [],
            'errors' => [],
        ], 201);
    }

    public function show(string $process): JsonResponse
    {
        $found = $this->repository->find($process);

        if ($found === null) {
            throw ProcessNotFoundException::forIdentifier($process);
        }

        return response()->json([
            'data' => $found->toArray(),
            'meta' => [],
            'errors' => [],
        ]);
    }

    public function update(UpdateProcessRequest $request, string $process): JsonResponse
    {
        $existing = $this->repository->find($process);

        if ($existing === null) {
            throw ProcessNotFoundException::forIdentifier($process);
        }

        $payload = array_merge($request->validated(), [
            'id' => $existing->id,
            'version' => $existing->version,
            'metadata' => $existing->metadata->toArray(),
        ]);

        try {
            $updated = ProcessDefinition::fromArray($payload)->withIncrementedVersion();
        } catch (InvalidProcessDefinitionException $exception) {
            return $this->invalidDefinitionResponse($exception);
        }

        if ($updated->slug !== $existing->slug) {
            $this->repository->delete($existing->slug);
        }

        $this->repository->save($updated);

        $this->auditLogger->record(AuditAction::ProcessUpdated, 'success', $updated->id, $updated->version);

        return response()->json([
            'data' => $updated->toArray(),
            'meta' => [],
            'errors' => [],
        ]);
    }

    public function destroy(string $process): JsonResponse
    {
        $existing = $this->repository->find($process);

        if ($existing === null) {
            throw ProcessNotFoundException::forIdentifier($process);
        }

        $this->repository->delete($process);

        $this->auditLogger->record(AuditAction::ProcessDeleted, 'success', $existing->id, $existing->version);

        return response()->json([
            'data' => null,
            'meta' => [],
            'errors' => [],
        ], 204);
    }

    public function duplicate(string $process): JsonResponse
    {
        $existing = $this->repository->find($process);

        if ($existing === null) {
            throw ProcessNotFoundException::forIdentifier($process);
        }

        $newSlug = $this->uniqueSlug($existing->slug);

        $payload = array_merge($existing->toArray(), [
            'id' => null,
            'slug' => $newSlug,
            'name' => $existing->name.' (Copy)',
            'version' => 1,
            'status' => 'draft',
            'metadata' => [],
        ]);

        $duplicate = ProcessDefinition::fromArray($payload);

        $this->repository->save($duplicate);

        return response()->json([
            'data' => $duplicate->toArray(),
            'meta' => [],
            'errors' => [],
        ], 201);
    }

    private function uniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug.'-copy';
        $suffix = 2;

        while ($this->repository->exists($slug)) {
            $slug = $baseSlug.'-copy-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function invalidDefinitionResponse(InvalidProcessDefinitionException $exception): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => [],
            'errors' => [['code' => 'process.invalid_definition', 'message' => $exception->getMessage()]],
        ], 422);
    }
}
