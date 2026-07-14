<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

final class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        config([
            'process-builder.enabled' => true,
            'process-builder.environments' => ['local', 'development', 'testing'],
            'process-builder.middleware' => ['web'],
            'process-builder.authorization_gate' => null,
        ]);
    }
}
