<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Contracts;

use MohamedZaki\LaravelProcessBuilder\DTO\ControllerInfo;

interface ControllerScannerContract
{
    public function inspect(string $class): ControllerInfo;
}
