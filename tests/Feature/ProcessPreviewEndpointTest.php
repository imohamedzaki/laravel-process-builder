<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Feature;

use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class ProcessPreviewEndpointTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/pb-preview-test-'.bin2hex(random_bytes(6));

        $this->app->instance(ProcessRepository::class, new FileProcessRepository($this->directory));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->directory)) {
            foreach (glob($this->directory.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->directory);
        }

        parent::tearDown();
    }

    public function test_previewing_a_valid_process_returns_generated_files_and_a_token(): void
    {
        $this->postJson('/process-builder/api/processes', [
            'name' => 'Create Order',
            'slug' => 'create-order',
            'entryNodeId' => 'r1',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => ['x' => 0, 'y' => 0], 'data' => ['method' => 'POST', 'uri' => '/orders', 'name' => 'orders.store']],
                ['id' => 'c1', 'type' => 'controller', 'position' => ['x' => 1, 'y' => 0], 'data' => ['class' => 'OrderController']],
                ['id' => 'resp', 'type' => 'response', 'position' => ['x' => 2, 'y' => 0], 'data' => []],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'r1', 'target' => 'c1'],
                ['id' => 'e2', 'source' => 'c1', 'target' => 'resp'],
            ],
        ])->assertCreated();

        $response = $this->postJson('/process-builder/api/processes/create-order/preview');

        $response->assertOk();
        $response->assertJsonPath('data.validation.valid', true);
        $response->assertJsonStructure(['data' => ['files', 'validation', 'previewToken']]);
        $this->assertNotNull($response->json('data.previewToken'));
    }

    public function test_previewing_never_writes_any_files_to_disk(): void
    {
        $this->postJson('/process-builder/api/processes', [
            'name' => 'Create Order',
            'slug' => 'create-order',
            'entryNodeId' => 'r1',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => ['x' => 0, 'y' => 0], 'data' => ['method' => 'POST', 'uri' => '/orders', 'name' => 'orders.store']],
                ['id' => 'c1', 'type' => 'controller', 'position' => ['x' => 1, 'y' => 0], 'data' => ['class' => 'OrderController']],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'r1', 'target' => 'c1'],
            ],
        ])->assertCreated();

        $this->postJson('/process-builder/api/processes/create-order/preview')->assertOk();

        $this->assertFileDoesNotExist(app_path('Http/Controllers/ProcessBuilder/OrderController.php'));
        $this->assertFileDoesNotExist(base_path('routes/process-builder.php'));
    }

    public function test_previewing_an_invalid_process_returns_no_token(): void
    {
        $this->postJson('/process-builder/api/processes', ['name' => 'Empty', 'slug' => 'empty'])->assertCreated();

        $response = $this->postJson('/process-builder/api/processes/empty/preview');

        $response->assertOk();
        $response->assertJsonPath('data.validation.valid', false);
        $this->assertNull($response->json('data.previewToken'));
    }

    public function test_previewing_a_missing_process_returns_404(): void
    {
        $response = $this->postJson('/process-builder/api/processes/does-not-exist/preview');

        $response->assertNotFound();
    }
}
