<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Feature;

use MohamedZaki\LaravelProcessBuilder\Contracts\ManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class ProcessGenerateEndpointTest extends TestCase
{
    private string $definitionsDirectory;

    private string $manifestsDirectory;

    private string $controllersDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $root = sys_get_temp_dir().'/pb-gen-endpoint-'.bin2hex(random_bytes(6));
        $this->definitionsDirectory = $root.'/definitions';
        $this->manifestsDirectory = $root.'/manifests';
        $this->controllersDirectory = $root.'/controllers';

        $this->app->instance(ProcessRepository::class, new FileProcessRepository($this->definitionsDirectory));
        $this->app->instance(ManifestRepository::class, new FileManifestRepository($this->manifestsDirectory));

        config([
            'process-builder.generation.enabled' => true,
            'process-builder.output.controllers' => $this->controllersDirectory,
            'process-builder.output.routes' => dirname($this->definitionsDirectory).'/routes/process-builder.php',
            'process-builder.audit.path' => $root.'/audit.log',
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(dirname($this->definitionsDirectory));

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

    private function createSimpleProcess(): void
    {
        $this->postJson('/process-builder/api/processes', [
            'name' => 'Create Order',
            'slug' => 'create-order',
            'entryNodeId' => 'start1',
            'nodes' => [
                ['id' => 'start1', 'type' => 'start', 'position' => ['x' => -1, 'y' => 0], 'data' => ['label' => 'create-order guard']],
                ['id' => 'r1', 'type' => 'route', 'position' => ['x' => 0, 'y' => 0], 'data' => ['method' => 'POST', 'uri' => '/orders', 'name' => 'orders.store']],
                ['id' => 'c1', 'type' => 'controller', 'position' => ['x' => 1, 'y' => 0], 'data' => ['class' => 'OrderController', 'method' => 'store']],
            ],
            'edges' => [
                ['id' => 'e0', 'source' => 'start1', 'target' => 'r1'],
                ['id' => 'e1', 'source' => 'r1', 'target' => 'c1'],
            ],
        ])->assertCreated();
    }

    public function test_it_generates_files_using_a_valid_preview_token(): void
    {
        $this->createSimpleProcess();

        $preview = $this->postJson('/process-builder/api/processes/create-order/preview');
        $preview->assertOk();
        $token = $preview->json('data.previewToken');

        $response = $this->postJson('/process-builder/api/processes/create-order/generate', ['previewToken' => $token]);

        $response->assertOk();
        $this->assertFileExists($this->controllersDirectory.'/OrderController.php');
        $routesPath = dirname($this->definitionsDirectory).'/routes/process-builder.php';
        $this->assertFileExists($routesPath);
        $this->assertStringContainsString('[OrderController::class, \'store\']', (string) file_get_contents($routesPath));
        $this->assertStringNotContainsString('abort(501)', (string) file_get_contents($routesPath));

        exec('php -l '.escapeshellarg($this->controllersDirectory.'/OrderController.php'), $controllerLint, $controllerExitCode);
        exec('php -l '.escapeshellarg($routesPath), $routesLint, $routesExitCode);
        $this->assertSame(0, $controllerExitCode, implode("\n", $controllerLint));
        $this->assertSame(0, $routesExitCode, implode("\n", $routesLint));

        $this->getJson('/process-builder/api/processes/create-order')
            ->assertOk()
            ->assertJsonPath('data.status', 'generated')
            ->assertJsonPath('data.metadata.generatorVersion', '0.1.0');
    }

    public function test_it_rejects_generation_without_a_token(): void
    {
        $this->createSimpleProcess();

        $response = $this->postJson('/process-builder/api/processes/create-order/generate', []);

        $response->assertStatus(422);
    }

    public function test_it_rejects_an_invalid_token(): void
    {
        $this->createSimpleProcess();

        $response = $this->postJson('/process-builder/api/processes/create-order/generate', ['previewToken' => 'garbage']);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.code', 'process.invalid_preview_token');
    }

    public function test_it_refuses_when_generation_is_disabled(): void
    {
        config(['process-builder.generation.enabled' => false]);

        $this->createSimpleProcess();

        $preview = $this->postJson('/process-builder/api/processes/create-order/preview');
        $token = $preview->json('data.previewToken');

        $response = $this->postJson('/process-builder/api/processes/create-order/generate', ['previewToken' => $token]);

        $response->assertStatus(403);
        $response->assertJsonPath('errors.0.code', 'process.generation_disabled');
    }

    public function test_generating_for_a_missing_process_returns_404(): void
    {
        $response = $this->postJson('/process-builder/api/processes/does-not-exist/generate', ['previewToken' => 'anything']);

        $response->assertNotFound();
    }
}
