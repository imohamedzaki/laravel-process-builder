<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class GenerationConflictException extends ProcessBuilderException
{
    public static function fileChangedSincePreview(string $relativePath): self
    {
        return new self("The file [{$relativePath}] has changed on disk since the preview was generated. Re-run preview before generating.");
    }

    public static function definitionChangedSincePreview(): self
    {
        return new self('The process definition has changed since the preview was generated. Re-run preview before generating.');
    }

    public static function managedFileModifiedManually(string $relativePath): self
    {
        return new self("The managed file [{$relativePath}] was modified outside of Laravel Process Builder. Use force to overwrite.");
    }
}
