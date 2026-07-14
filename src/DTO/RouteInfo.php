<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

final class RouteInfo
{
    /**
     * @param  list<string>  $methods
     * @param  list<string>  $middleware
     * @param  list<string>  $parameters
     */
    public function __construct(
        public readonly array $methods,
        public readonly string $uri,
        public readonly ?string $name,
        public readonly ?string $domain,
        public readonly ?string $action,
        public readonly ?string $controller,
        public readonly ?string $controllerMethod,
        public readonly array $middleware,
        public readonly array $parameters,
        public readonly bool $isVendorRoute,
        public readonly bool $isPackageInternal,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'methods' => $this->methods,
            'uri' => $this->uri,
            'name' => $this->name,
            'domain' => $this->domain,
            'action' => $this->action,
            'controller' => $this->controller,
            'controllerMethod' => $this->controllerMethod,
            'middleware' => $this->middleware,
            'parameters' => $this->parameters,
            'isVendorRoute' => $this->isVendorRoute,
            'isPackageInternal' => $this->isPackageInternal,
        ];
    }
}
