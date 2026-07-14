<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Enums;

enum ProcessStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Generated = 'generated';
    case Archived = 'archived';
}
