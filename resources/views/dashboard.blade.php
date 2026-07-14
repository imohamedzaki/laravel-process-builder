<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="process-builder-base-path" content="{{ $basePath }}">
    @php($manifestPath = __DIR__.'/../../dist/.vite/manifest.json')
    @if (file_exists($manifestPath))
        @php($manifest = json_decode(file_get_contents($manifestPath), true))
        @php($entry = $manifest['resources/js/app.tsx'] ?? null)
        @if ($entry)
            <link rel="stylesheet" href="{{ asset('vendor/process-builder/'.($entry['css'][0] ?? '')) }}">
            <script type="module" src="{{ asset('vendor/process-builder/'.$entry['file']) }}"></script>
        @endif
    @else
        <script type="module" src="http://localhost:5173/{{ '@' }}vite/client"></script>
        <script type="module" src="http://localhost:5173/resources/js/app.tsx"></script>
    @endif
</head>
<body>
    <div id="process-builder-root" data-app-name="{{ $appName }}" data-tagline="{{ $tagline }}" data-version="{{ $version }}">
        <noscript>Laravel Process Builder requires JavaScript to be enabled.</noscript>
    </div>
</body>
</html>
