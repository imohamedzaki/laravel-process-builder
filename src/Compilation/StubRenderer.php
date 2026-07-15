<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Compilation;

final class StubRenderer
{
    public function __construct(private readonly string $stubsDirectory)
    {
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function render(string $stubName, array $replacements): string
    {
        $path = $this->stubsDirectory.DIRECTORY_SEPARATOR.$stubName.'.stub';

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Stub [{$stubName}] could not be read from [{$path}].");
        }

        $search = array_map(static fn (string $key): string => '{{ '.$key.' }}', array_keys($replacements));

        $rendered = str_replace($search, array_values($replacements), $contents);

        return $this->normalizeBlankLines($rendered);
    }

    private function normalizeBlankLines(string $contents): string
    {
        $contents = preg_replace("/\n{3,}/", "\n\n", $contents) ?? $contents;

        return rtrim($contents)."\n";
    }
}
