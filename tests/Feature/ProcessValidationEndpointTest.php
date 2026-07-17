<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Feature;

use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class ProcessValidationEndpointTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/pb-validate-test-'.bin2hex(random_bytes(6));

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

    public function test_it_validates_an_empty_process_as_invalid(): void
    {
        $this->postJson('/process-builder/api/processes', ['name' => 'Empty', 'slug' => 'empty'])->assertCreated();

        $response = $this->postJson('/process-builder/api/processes/empty/validate');

        $response->assertOk();
        $response->assertJsonPath('data.valid', false);
    }

    public function test_it_validates_a_well_formed_process_as_valid(): void
    {
        $this->postJson('/process-builder/api/processes', [
            'name' => 'Create Order',
            'slug' => 'create-order',
            'participants' => [['id' => 'participant_web', 'name' => 'Web user', 'guard' => 'web', 'actorType' => 'human', 'order' => 0, 'color' => null]],
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

        $response = $this->postJson('/process-builder/api/processes/create-order/validate');

        $response->assertOk();
        $response->assertJsonPath('data.valid', true);
    }

    public function test_validating_a_missing_process_returns_404(): void
    {
        $response = $this->postJson('/process-builder/api/processes/does-not-exist/validate');

        $response->assertNotFound();
    }
}
