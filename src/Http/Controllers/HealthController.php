<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use MohamedZaki\LaravelProcessBuilder\LaravelProcessBuilderServiceProvider;

final class HealthController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'status' => 'ok',
                'version' => LaravelProcessBuilderServiceProvider::VERSION,
                'generationEnabled' => (bool) config('process-builder.generation.enabled', false),
                'environment' => app()->environment(),
            ],
            'meta' => [],
            'errors' => [],
        ]);
    }
}
