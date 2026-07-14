<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Exceptions;

final class UnsupportedNodeException extends ProcessBuilderException
{
    public static function forType(string $type): self
    {
        return new self("Node type [{$type}] is not supported.");
    }
}
