<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Repositories;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Exceptions\ProcessNotFoundException;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class FileProcessRepositoryTest extends TestCase
{
    private string $directory;

    private FileProcessRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/pb-repo-test-'.bin2hex(random_bytes(6));
        $this->repository = new FileProcessRepository($this->directory);
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

    public function test_it_creates_the_directory_on_demand(): void
    {
        $this->assertDirectoryDoesNotExist($this->directory);

        $this->repository->all();

        $this->assertDirectoryExists($this->directory);
    }

    public function test_it_saves_and_finds_a_process_by_slug(): void
    {
        $process = ProcessDefinition::fromArray(['name' => 'Create Order', 'slug' => 'create-order']);

        $this->repository->save($process);

        $found = $this->repository->find('create-order');

        $this->assertNotNull($found);
        $this->assertSame($process->id, $found->id);
    }

    public function test_it_finds_a_process_by_id(): void
    {
        $process = ProcessDefinition::fromArray(['name' => 'Create Order', 'slug' => 'create-order']);

        $this->repository->save($process);

        $found = $this->repository->find($process->id);

        $this->assertNotNull($found);
        $this->assertSame('create-order', $found->slug);
    }

    public function test_it_returns_null_for_a_missing_process(): void
    {
        $this->assertNull($this->repository->find('does-not-exist'));
    }

    public function test_it_lists_all_processes(): void
    {
        $this->repository->save(ProcessDefinition::fromArray(['name' => 'B', 'slug' => 'b']));
        $this->repository->save(ProcessDefinition::fromArray(['name' => 'A', 'slug' => 'a']));

        $all = $this->repository->all();

        $this->assertSame(2, $all->count());
        $this->assertSame('A', $all->all()[0]->name);
    }

    public function test_it_deletes_a_process(): void
    {
        $process = ProcessDefinition::fromArray(['name' => 'Delete Me', 'slug' => 'delete-me']);
        $this->repository->save($process);

        $this->repository->delete('delete-me');

        $this->assertFalse($this->repository->exists('delete-me'));
    }

    public function test_deleting_a_missing_process_throws(): void
    {
        $this->expectException(ProcessNotFoundException::class);

        $this->repository->delete('does-not-exist');
    }

    public function test_finding_a_path_traversal_identifier_returns_null_instead_of_touching_disk(): void
    {
        // A path-traversal-shaped identifier is never a valid slug, so it must not
        // be used to build a filesystem path — it should just report "not found".
        $this->assertNull($this->repository->find('../../etc/passwd'));
    }

    public function test_saving_produces_readable_formatted_json(): void
    {
        $process = ProcessDefinition::fromArray(['name' => 'Pretty', 'slug' => 'pretty']);

        $this->repository->save($process);

        $contents = file_get_contents($this->directory.'/pretty.json');

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("\n", $contents);
        $this->assertStringContainsString('"name": "Pretty"', $contents);
    }
}
