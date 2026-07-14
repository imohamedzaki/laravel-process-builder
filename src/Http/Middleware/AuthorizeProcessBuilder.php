<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthorizeProcessBuilder
{
    public function __construct(private readonly Gate $gate)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $ability = config('process-builder.authorization_gate');

        if (is_string($ability) && $ability !== '' && $this->gate->has($ability)) {
            if (! $this->gate->forUser($request->user())->allows($ability)) {
                abort(403, 'You are not authorized to access Laravel Process Builder.');
            }
        }

        return $next($request);
    }
}
