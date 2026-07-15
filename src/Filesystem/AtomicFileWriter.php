<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Filesystem;

use MohamedZaki\LaravelProcessBuilder\Exceptions\PhpSyntaxException;

final class AtomicFileWriter
{
    public function write(string $absolutePath, string $contents, bool $validateSyntax = true): void
    {
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $tempPath = $absolutePath.'.'.bin2hex(random_bytes(6)).'.tmp';

        $handle = fopen($tempPath, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open [{$tempPath}] for writing.");
        }

        try {
            flock($handle, LOCK_EX);
            fwrite($handle, $contents);
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        if ($validateSyntax && str_ends_with($absolutePath, '.php')) {
            $this->assertValidSyntax($tempPath);
        }

        rename($tempPath, $absolutePath);
    }

    private function assertValidSyntax(string $path): void
    {
        $output = [];
        $exitCode = 0;

        exec('php -l '.escapeshellarg($path).' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            unlink($path);

            throw PhpSyntaxException::forFile($path, implode("\n", $output));
        }
    }
}
