<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Feature;

use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class ProcessCrudEndpointsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/pb-api-test-'.bin2hex(random_bytes(6));

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

    public function test_it_creates_a_process(): void
    {
        $response = $this->postJson('/process-builder/api/processes', [
            'name' => 'Create Order',
            'slug' => 'create-order',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.slug', 'create-order');
        $response->assertJsonPath('data.participants', []);
        $response->assertJsonPath('data.version', 1);
    }

    public function test_it_rejects_creating_a_duplicate_slug(): void
    {
        $this->postJson('/process-builder/api/processes', ['name' => 'A', 'slug' => 'dup'])->assertCreated();

        $response = $this->postJson('/process-builder/api/processes', ['name' => 'B', 'slug' => 'dup']);

        $response->assertStatus(409);
    }

    public function test_the_same_guard_can_participate_in_multiple_processes(): void
    {
        $participant = [['id' => 'shared', 'name' => 'Shared', 'guard' => 'web', 'actorType' => 'human', 'order' => 0]];
        $this->postJson('/process-builder/api/processes', ['name' => 'A', 'slug' => 'a', 'participants' => $participant])->assertCreated();

        $this->postJson('/process-builder/api/processes', ['name' => 'B', 'slug' => 'b', 'participants' => $participant])
            ->assertCreated()
            ->assertJsonPath('data.participants.0.guard', 'web');
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->postJson('/process-builder/api/processes', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_it_lists_processes(): void
    {
        $this->postJson('/process-builder/api/processes', ['name' => 'A', 'slug' => 'a'])->assertCreated();
        $this->postJson('/process-builder/api/processes', ['name' => 'B', 'slug' => 'b'])->assertCreated();

        $response = $this->getJson('/process-builder/api/processes');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_it_shows_a_single_process(): void
    {
        $this->postJson('/process-builder/api/processes', ['name' => 'Show Me', 'slug' => 'show-me'])->assertCreated();

        $response = $this->getJson('/process-builder/api/processes/show-me');

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Show Me');
    }

    public function test_showing_a_missing_process_returns_404(): void
    {
        $response = $this->getJson('/process-builder/api/processes/does-not-exist');

        $response->assertNotFound();
        $response->assertJsonPath('errors.0.code', 'process.not_found');
    }

    public function test_it_updates_a_process_and_increments_version(): void
    {
        $this->postJson('/process-builder/api/processes', ['name' => 'Original', 'slug' => 'updatable'])->assertCreated();

        $response = $this->putJson('/process-builder/api/processes/updatable', [
            'name' => 'Updated Name',
            'slug' => 'updatable',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Name');
        $response->assertJsonPath('data.version', 2);
    }

    public function test_it_deletes_a_process(): void
    {
        $this->postJson('/process-builder/api/processes', ['name' => 'Deletable', 'slug' => 'deletable'])->assertCreated();

        $response = $this->deleteJson('/process-builder/api/processes/deletable');

        $response->assertStatus(204);
        $this->getJson('/process-builder/api/processes/deletable')->assertNotFound();
    }

    public function test_it_duplicates_a_process(): void
    {
        $this->postJson('/process-builder/api/processes', ['name' => 'Original', 'slug' => 'original'])->assertCreated();

        $response = $this->postJson('/process-builder/api/processes/original/duplicate');

        $response->assertCreated();
        $response->assertJsonPath('data.slug', 'original-copy');
        $response->assertJsonPath('data.name', 'Original (Copy)');
        $response->assertJsonPath('data.version', 1);
    }
}
