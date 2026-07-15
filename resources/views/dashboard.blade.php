<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="process-builder-base-path" content="{{ $basePath }}">
    @php($manifestPath = public_path('vendor/process-builder/.vite/manifest.json'))
    @php($devServerRunning = app()->environment('local') && @fsockopen('127.0.0.1', 5173, $errno, $errstr, 0.1))
    @if (file_exists($manifestPath))
        @php($manifest = json_decode(file_get_contents($manifestPath), true))
        @php($entry = $manifest['resources/js/app.tsx'] ?? null)
        @if ($entry)
            <link rel="stylesheet" href="{{ asset('vendor/process-builder/'.($entry['css'][0] ?? '')) }}">
            <script type="module" src="{{ asset('vendor/process-builder/'.$entry['file']) }}"></script>
        @endif
    @elseif ($devServerRunning)
        <script type="module" src="http://localhost:5173/{{ '@' }}vite/client"></script>
        <script type="module" src="http://localhost:5173/resources/js/app.tsx"></script>
    @endif
</head>
<body>
    <div id="process-builder-root" data-app-name="{{ $appName }}" data-tagline="{{ $tagline }}" data-version="{{ $version }}">
        <noscript>Laravel Process Builder requires JavaScript to be enabled.</noscript>
        @if (! file_exists($manifestPath) && ! $devServerRunning)
            <div style="font-family: ui-sans-serif, system-ui, sans-serif; max-width: 640px; margin: 4rem auto; padding: 1.5rem 2rem; border: 1px solid #f0b429; border-radius: 8px; background: #fffbeb; color: #78350f;">
                <h1 style="font-size: 1.1rem; margin: 0 0 .75rem;">Process Builder assets are not published</h1>
                <p style="margin: 0 0 .75rem;">The dashboard couldn't find its built JavaScript/CSS assets, so nothing rendered.</p>
                <p style="margin: 0 0 .5rem;">Run this in your project root, then reload this page:</p>
                <pre style="background: #1f2937; color: #f9fafb; padding: .75rem 1rem; border-radius: 6px; overflow-x: auto;">php artisan vendor:publish --tag=process-builder-assets</pre>
                <p style="margin: .75rem 0 0; font-size: .875rem;">If you're developing the package itself, start the Vite dev server instead (<code>npm run dev</code>) so assets are served from <code>localhost:5173</code>.</p>
            </div>
        @endif
    </div>
</body>
</html>
