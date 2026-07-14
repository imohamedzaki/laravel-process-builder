<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Enums;

enum ValidationSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
}
