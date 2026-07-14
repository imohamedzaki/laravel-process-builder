<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Filesystem;

use MohamedZaki\LaravelProcessBuilder\Exceptions\UnsafeOutputPathException;

final class SafePath
{
    /**
     * Determine whether a slug is safe to use as a filename component, without throwing.
     */
    public static function isSafeSlug(string $slug): bool
    {
        return $slug !== '' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }

    /**
     * Validate a slug is safe to use as a filename component (no path traversal, no separators).
     */
    public static function assertSafeSlug(string $slug): void
    {
        if (! self::isSafeSlug($slug)) {
            throw UnsafeOutputPathException::traversalDetected($slug);
        }
    }

    /**
     * Resolve a filename inside a base directory, guaranteeing the result stays within it.
     *
     * The filename must be a bare "{slug}.json"-style name — it may not contain any
     * directory separators, since every caller in this package only ever builds
     * paths from a validated slug plus a fixed extension.
     */
    public static function resolveWithin(string $baseDirectory, string $filename): string
    {
        if (str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw UnsafeOutputPathException::traversalDetected($filename);
        }

        self::assertSafeSlug(pathinfo($filename, PATHINFO_FILENAME));

        return rtrim($baseDirectory, '/\\').DIRECTORY_SEPARATOR.$filename;
    }
}
