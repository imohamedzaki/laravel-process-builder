<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Scanning;

use MohamedZaki\LaravelProcessBuilder\Scanning\ControllerScanner;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use Workbench\App\Http\Controllers\InvokableController;
use Workbench\App\Http\Controllers\OrderController;

final class ControllerScannerTest extends TestCase
{
    public function test_it_inspects_an_existing_controller(): void
    {
        $info = (new ControllerScanner())->inspect(OrderController::class);

        $this->assertTrue($info->exists);
        $this->assertSame(OrderController::class, $info->class);
        $this->assertNotNull($info->filePath);
        $this->assertFalse($info->isInvokable);
    }

    public function test_it_only_lists_public_declared_methods(): void
    {
        $info = (new ControllerScanner())->inspect(OrderController::class);

        $methodNames = array_map(static fn ($method) => $method->name, $info->methods);

        $this->assertContains('index', $methodNames);
        $this->assertContains('store', $methodNames);
        $this->assertContains('show', $methodNames);
        $this->assertNotContains('notExposed', $methodNames);
        $this->assertNotContains('__construct', $methodNames);
    }

    public function test_it_detects_form_request_style_parameters(): void
    {
        $info = (new ControllerScanner())->inspect(OrderController::class);

        $store = $info->methodByName('store');

        $this->assertNotNull($store);
        $this->assertSame('Illuminate\Http\Request', $store->formRequestParameter);
    }

    public function test_it_captures_constructor_dependencies(): void
    {
        $info = (new ControllerScanner())->inspect(OrderController::class);

        $this->assertCount(1, $info->constructorDependencies);
        $this->assertSame('Illuminate\Http\Request', $info->constructorDependencies[0]->type);
    }

    public function test_it_detects_invokable_controllers(): void
    {
        $info = (new ControllerScanner())->inspect(InvokableController::class);

        $this->assertTrue($info->isInvokable);
    }

    public function test_it_returns_errors_instead_of_crashing_for_missing_class(): void
    {
        $info = (new ControllerScanner())->inspect('App\Http\Controllers\DoesNotExist');

        $this->assertFalse($info->exists);
        $this->assertSame([], $info->methods);
    }

    public function test_it_captures_parameter_types_for_route_model_binding(): void
    {
        $info = (new ControllerScanner())->inspect(OrderController::class);

        $show = $info->methodByName('show');

        $this->assertNotNull($show);
        $this->assertSame('order', $show->parameters[0]->name);
        $this->assertSame('int', $show->parameters[0]->type);
    }
}
