<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Controllers;

use Illuminate\Contracts\View\View;
use MohamedZaki\LaravelProcessBuilder\LaravelProcessBuilderServiceProvider;

final class DashboardController
{
    public function __invoke(): View
    {
        return view('process-builder::dashboard', [
            'appName' => 'Laravel Process Builder',
            'tagline' => 'Design Laravel processes visually. Generate clean Laravel code.',
            'version' => LaravelProcessBuilderServiceProvider::VERSION,
            'basePath' => config('process-builder.path'),
        ]);
    }
}
