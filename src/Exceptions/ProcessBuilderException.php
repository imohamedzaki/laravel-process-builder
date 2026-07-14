<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

abstract class ProcessBuilderException extends RuntimeException
{
    protected function httpStatus(): int
    {
        return 400;
    }

    protected function errorCode(): string
    {
        return 'process-builder.error';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => [],
            'errors' => [[
                'code' => $this->errorCode(),
                'message' => $this->getMessage(),
            ]],
        ], $this->httpStatus());
    }
}
