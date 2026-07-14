<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController
{
    public function __construct(private readonly Request $request)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([], 201);
    }

    public function show(int $order): JsonResponse
    {
        return response()->json(['id' => $order]);
    }

    private function notExposed(): void
    {
    }
}
