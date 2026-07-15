<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Validation;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\RouteRule;

final class RouteRuleTest extends TestCase
{
    public function test_it_rejects_an_unsupported_http_method(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Bad Method',
            'slug' => 'bad-method',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => [], 'data' => ['method' => 'TRACE', 'uri' => '/x']],
            ],
        ]);

        $result = (new RouteRule())->validate($process);

        $this->assertContains('route.invalid_method', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_requires_a_uri(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'No URI',
            'slug' => 'no-uri',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => [], 'data' => ['method' => 'GET']],
            ],
        ]);

        $result = (new RouteRule())->validate($process);

        $this->assertContains('route.invalid_uri', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_requires_a_controller_connection(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'No Controller',
            'slug' => 'no-controller',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => [], 'data' => ['method' => 'GET', 'uri' => '/x']],
            ],
        ]);

        $result = (new RouteRule())->validate($process);

        $this->assertContains('route.controller_missing', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_passes_for_a_well_formed_route_connected_to_a_controller(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Good Route',
            'slug' => 'good-route',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => [], 'data' => ['method' => 'POST', 'uri' => '/orders', 'name' => 'orders.store']],
                ['id' => 'c1', 'type' => 'controller', 'position' => [], 'data' => []],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'r1', 'target' => 'c1'],
            ],
        ]);

        $result = (new RouteRule())->validate($process);

        $this->assertTrue($result->isValid());
    }

    public function test_it_rejects_invalid_middleware_values(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Bad Middleware',
            'slug' => 'bad-middleware',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => [], 'data' => ['method' => 'GET', 'uri' => '/x', 'middleware' => ['', 123]]],
            ],
        ]);

        $result = (new RouteRule())->validate($process);

        $this->assertContains('route.invalid_middleware', array_map(static fn ($e) => $e->code, $result->errors()));
    }
}
