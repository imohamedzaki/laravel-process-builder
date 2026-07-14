<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Contracts;

use MohamedZaki\LaravelProcessBuilder\DTO\RouteInfo;

interface RouteScannerContract
{
    /**
     * @return list<RouteInfo>
     */
    public function scan(): array;
}
