<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use MohamedZaki\LaravelProcessBuilder\Scanning\ProjectScanner;

final class ProjectController
{
    public function __construct(private readonly ProjectScanner $scanner)
    {
    }

    public function __invoke(): JsonResponse
    {
        $summary = $this->scanner->summarize();

        return response()->json([
            'data' => $summary->toArray(),
            'meta' => [],
            'errors' => [],
        ]);
    }
}
