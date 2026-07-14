<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureProcessBuilderIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('process-builder.enabled', false)) {
            abort(404);
        }

        $allowedEnvironments = (array) config('process-builder.environments', []);

        if ($allowedEnvironments !== [] && ! app()->environment($allowedEnvironments)) {
            abort(404);
        }

        return $next($request);
    }
}
