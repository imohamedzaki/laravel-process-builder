<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Feature\Console;

use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class DemoCommandTest extends TestCase
{
    private string $definitionsDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->definitionsDirectory = sys_get_temp_dir().'/pb-demo-'.bin2hex(random_bytes(6));

        $this->app->instance(ProcessRepository::class, new FileProcessRepository($this->definitionsDirectory));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->definitionsDirectory);

        parent::tearDown();
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (glob($directory.'/*') ?: [] as $entry) {
            is_dir($entry) ? $this->removeDirectory($entry) : unlink($entry);
        }

        rmdir($directory);
    }

    public function test_it_installs_both_bundled_demo_processes(): void
    {
        $this->artisan('process-builder:demo')->assertExitCode(0);

        $repository = $this->app->make(ProcessRepository::class);

        $this->assertTrue($repository->exists('create-order'));
        $this->assertTrue($repository->exists('approve-leave-request'));

        $createOrder = $repository->find('create-order');
        $this->assertNotNull($createOrder);
        $this->assertSame('Create Order', $createOrder->name);
        $this->assertNotEmpty($createOrder->nodes);

        $approveLeave = $repository->find('approve-leave-request');
        $this->assertNotNull($approveLeave);
        $this->assertSame('Approve Leave Request', $approveLeave->name);
        $this->assertNotEmpty($approveLeave->nodes);
    }

    public function test_installed_demo_processes_pass_validation(): void
    {
        $this->artisan('process-builder:demo')->assertExitCode(0);

        $this->artisan('process-builder:validate')->assertExitCode(0);
    }

    public function test_it_skips_existing_demo_without_force(): void
    {
        $this->artisan('process-builder:demo')->assertExitCode(0);

        $repository = $this->app->make(ProcessRepository::class);
        $original = $repository->find('create-order');
        $this->assertNotNull($original);

        $this->artisan('process-builder:demo')->assertExitCode(0);

        $reloaded = $repository->find('create-order');
        $this->assertNotNull($reloaded);
        $this->assertSame($original->id, $reloaded->id);
    }

    public function test_it_overwrites_existing_demo_with_force(): void
    {
        $this->artisan('process-builder:demo')->assertExitCode(0);

        $this->artisan('process-builder:demo', ['--force' => true])->assertExitCode(0);

        $repository = $this->app->make(ProcessRepository::class);
        $this->assertTrue($repository->exists('create-order'));
    }
}
