<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Generation;

use MohamedZaki\LaravelProcessBuilder\Backup\BackupService;
use MohamedZaki\LaravelProcessBuilder\Compilation\ActionCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\ApiResourceCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\ClassNameResolver;
use MohamedZaki\LaravelProcessBuilder\Compilation\ControllerCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\EventCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\FormRequestCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\JobCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\ProcessCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\RouteFileCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\StubRenderer;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Exceptions\FileOwnershipConflictException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\GenerationConflictException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\GenerationDisabledException;
use MohamedZaki\LaravelProcessBuilder\Exceptions\UnauthorizedEnvironmentException;
use MohamedZaki\LaravelProcessBuilder\Filesystem\AtomicFileWriter;
use MohamedZaki\LaravelProcessBuilder\Generation\GenerationService;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Security\PreviewToken;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\AllowedConnectionsRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\ClassNameRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\FormRequestRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\GraphStructureRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\RouteCollisionRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\RouteRule;
use MohamedZaki\LaravelProcessBuilder\Validation\ValidationPipeline;

final class GenerationServiceTest extends TestCase
{
    private string $outputDirectory;

    private string $manifestsDirectory;

    private string $backupsDirectory;

    private string $lockDirectory;

    private string $definitionsDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $root = sys_get_temp_dir().'/pb-gen-test-'.bin2hex(random_bytes(6));
        $this->outputDirectory = $root.'/controllers';
        $this->manifestsDirectory = $root.'/manifests';
        $this->backupsDirectory = $root.'/backups';
        $this->lockDirectory = $root.'/locks';
        $this->definitionsDirectory = $root.'/definitions';
    }

    protected function tearDown(): void
    {
        $root = dirname($this->outputDirectory);
        $this->removeDirectory($root);

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

    private function simpleProcess(): ProcessDefinition
    {
        $repository = new FileProcessRepository($this->definitionsDirectory);

        $process = ProcessDefinition::fromArray([
            'name' => 'Create Order',
            'slug' => 'create-order',
            'entryNodeId' => 'r1',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => ['x' => 0, 'y' => 0], 'data' => ['method' => 'POST', 'uri' => '/orders', 'name' => 'orders.store']],
                ['id' => 'c1', 'type' => 'controller', 'position' => ['x' => 1, 'y' => 0], 'data' => ['class' => 'OrderController', 'method' => 'store']],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'r1', 'target' => 'c1'],
            ],
        ]);

        $repository->save($process);

        return $process;
    }

    private function service(bool $generationEnabled = true, array $allowedEnvironments = ['testing'], bool $createBackups = true): GenerationService
    {
        $renderer = new StubRenderer(__DIR__.'/../../../resources/stubs');
        $resolver = new ClassNameResolver();
        $processRepository = new FileProcessRepository($this->definitionsDirectory);
        $this->app->instance(ProcessRepository::class, $processRepository);

        $pipeline = new ValidationPipeline([
            new GraphStructureRule(),
            new AllowedConnectionsRule(),
            new RouteRule(),
            new RouteCollisionRule($processRepository),
            new ClassNameRule(),
            new FormRequestRule(),
        ]);

        $compiler = new ProcessCompiler(
            validationPipeline: $pipeline,
            routeFileCompiler: new RouteFileCompiler(),
            controllerCompiler: new ControllerCompiler($renderer, $resolver),
            formRequestCompiler: new FormRequestCompiler($renderer, $resolver),
            actionCompiler: new ActionCompiler($renderer, $resolver),
            eventCompiler: new EventCompiler($renderer, $resolver),
            jobCompiler: new JobCompiler($renderer, $resolver),
            apiResourceCompiler: new ApiResourceCompiler($renderer, $resolver),
            outputDirectories: [
                'basePath' => sys_get_temp_dir().'/',
                'routes' => $this->outputDirectory.'/routes/process-builder.php',
                'controllers' => $this->outputDirectory,
            ],
        );

        return new GenerationService(
            compiler: $compiler,
            writer: new AtomicFileWriter(),
            backupService: new BackupService($this->backupsDirectory),
            manifestRepository: new FileManifestRepository($this->manifestsDirectory),
            lockDirectory: $this->lockDirectory,
            generationEnabled: $generationEnabled,
            allowedEnvironments: $allowedEnvironments,
            currentEnvironment: 'testing',
            createBackups: $createBackups,
            validateSyntax: true,
        );
    }

    private function tokenFor(ProcessDefinition $process): PreviewToken
    {
        $checksum = hash('sha256', json_encode($process->toArray(), JSON_THROW_ON_ERROR));

        return new PreviewToken($process->id, $process->version, $checksum, time() + 600);
    }

    public function test_it_generates_files_successfully(): void
    {
        $process = $this->simpleProcess();

        $result = $this->service()->generate($process, $this->tokenFor($process));

        $this->assertGreaterThan(0, $result->files->count());
        $this->assertFileExists($this->outputDirectory.'/OrderController.php');
    }

    public function test_it_refuses_when_generation_is_disabled(): void
    {
        $process = $this->simpleProcess();

        $this->expectException(GenerationDisabledException::class);

        $this->service(generationEnabled: false)->generate($process, $this->tokenFor($process));
    }

    public function test_it_refuses_in_an_unauthorized_environment(): void
    {
        $process = $this->simpleProcess();

        $this->expectException(UnauthorizedEnvironmentException::class);

        $this->service(allowedEnvironments: ['production'])->generate($process, $this->tokenFor($process));
    }

    public function test_it_refuses_when_the_token_does_not_match_the_process(): void
    {
        $process = $this->simpleProcess();

        $mismatchedToken = new PreviewToken('some-other-id', $process->version, 'bad-checksum', time() + 600);

        $this->expectException(GenerationConflictException::class);

        $this->service()->generate($process, $mismatchedToken);
    }

    public function test_it_refuses_when_the_definition_checksum_no_longer_matches(): void
    {
        $process = $this->simpleProcess();

        $staleToken = new PreviewToken($process->id, $process->version, 'stale-checksum', time() + 600);

        $this->expectException(GenerationConflictException::class);

        $this->service()->generate($process, $staleToken);
    }

    public function test_it_creates_a_backup_when_overwriting_an_existing_managed_file(): void
    {
        $process = $this->simpleProcess();
        $service = $this->service();

        $first = $service->generate($process, $this->tokenFor($process));
        $this->assertNotNull($first->backup);

        // Regenerating the identical process should back up the existing (matching) managed file.
        $second = $service->generate($process, $this->tokenFor($process));

        $this->assertNotNull($second->backup);
    }

    public function test_it_refuses_to_overwrite_a_file_never_tracked_by_this_process(): void
    {
        mkdir($this->outputDirectory, 0755, true);
        file_put_contents($this->outputDirectory.'/OrderController.php', '<?php // hand-written, not managed');

        $process = $this->simpleProcess();

        $this->expectException(FileOwnershipConflictException::class);

        $this->service()->generate($process, $this->tokenFor($process));
    }

    public function test_it_refuses_to_overwrite_a_managed_file_that_was_hand_edited(): void
    {
        $process = $this->simpleProcess();
        $service = $this->service();

        $service->generate($process, $this->tokenFor($process));

        // Simulate a developer hand-editing the generated file afterward.
        file_put_contents($this->outputDirectory.'/OrderController.php', '<?php // manually modified');

        $this->expectException(GenerationConflictException::class);

        $service->generate($process, $this->tokenFor($process));
    }

    public function test_force_allows_overwriting_a_hand_edited_managed_file(): void
    {
        $process = $this->simpleProcess();
        $service = $this->service();

        $service->generate($process, $this->tokenFor($process));

        file_put_contents($this->outputDirectory.'/OrderController.php', '<?php // manually modified');

        $result = $service->generate($process, $this->tokenFor($process), force: true);

        $this->assertGreaterThan(0, $result->files->count());
        $this->assertStringNotContainsString('manually modified', (string) file_get_contents($this->outputDirectory.'/OrderController.php'));
    }

    public function test_generated_files_contain_the_managed_marker(): void
    {
        $process = $this->simpleProcess();

        $this->service()->generate($process, $this->tokenFor($process));

        $contents = (string) file_get_contents($this->outputDirectory.'/OrderController.php');

        $this->assertStringContainsString('This file is managed by Laravel Process Builder.', $contents);
    }
}
