<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class InvokableController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([]);
    }
}
