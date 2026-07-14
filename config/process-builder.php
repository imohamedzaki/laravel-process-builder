<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard enabled
    |--------------------------------------------------------------------------
    */
    'enabled' => env('PROCESS_BUILDER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Dashboard path
    |--------------------------------------------------------------------------
    */
    'path' => env('PROCESS_BUILDER_PATH', 'process-builder'),

    'api_prefix' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Authorized environments
    |--------------------------------------------------------------------------
    |
    | The dashboard and API are only reachable when the application's current
    | environment is present in this list, regardless of the "enabled" flag.
    |
    */
    'environments' => [
        'local',
        'development',
        'testing',
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization gate
    |--------------------------------------------------------------------------
    |
    | The name of the gate that will be checked before granting access to the
    | dashboard and API. Define this gate in your application's AuthServiceProvider.
    | When null, no gate check is performed (authorization is left to middleware).
    |
    */
    'authorization_gate' => 'manage-process-builder',

    /*
    |--------------------------------------------------------------------------
    | Code generation
    |--------------------------------------------------------------------------
    */
    'generation' => [
        'enabled' => env('PROCESS_BUILDER_GENERATION_ENABLED', false),

        'require_preview' => true,

        'require_confirmation_token' => true,

        'preview_token_ttl' => 600,

        'create_backups' => true,

        'validate_php_syntax' => true,

        'managed_marker_required' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Process definitions storage
    |--------------------------------------------------------------------------
    */
    'definitions' => [
        'path' => base_path('process-builder/definitions'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generation manifests storage
    |--------------------------------------------------------------------------
    */
    'manifests' => [
        'path' => base_path('process-builder/manifests'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backups
    |--------------------------------------------------------------------------
    */
    'backups' => [
        'path' => storage_path('app/process-builder/backups'),
        'retention' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit log
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'path' => storage_path('app/process-builder/audit.log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Managed output directories
    |--------------------------------------------------------------------------
    |
    | These are the only directories the generator is ever allowed to write
    | into. Every path must be an absolute path resolved from trusted config,
    | never from user/browser input.
    |
    */
    'output' => [
        'routes' => base_path('routes/process-builder.php'),

        'controllers' => app_path('Http/Controllers/ProcessBuilder'),

        'requests' => app_path('Http/Requests/ProcessBuilder'),

        'actions' => app_path('Actions/ProcessBuilder'),

        'services' => app_path('Services/ProcessBuilder'),

        'events' => app_path('Events/ProcessBuilder'),

        'jobs' => app_path('Jobs/ProcessBuilder'),

        'notifications' => app_path('Notifications/ProcessBuilder'),

        'resources' => app_path('Http/Resources/ProcessBuilder'),

        'tests' => base_path('tests/Feature/ProcessBuilder'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scanner
    |--------------------------------------------------------------------------
    */
    'scanner' => [
        'exclude_uri_prefixes' => [
            'process-builder',
            '_debugbar',
            'telescope',
            'horizon',
            'sanctum',
        ],

        'exclude_namespaces' => [
            'MohamedZaki\\LaravelProcessBuilder',
        ],
    ],
];
