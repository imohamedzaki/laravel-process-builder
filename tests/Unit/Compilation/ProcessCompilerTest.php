<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Compilation;

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
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\AllowedConnectionsRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\ClassNameRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\FormRequestRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\GraphStructureRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\RouteCollisionRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\RouteRule;
use MohamedZaki\LaravelProcessBuilder\Validation\ValidationPipeline;

final class ProcessCompilerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/pb-compiler-test-'.bin2hex(random_bytes(6));
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

    private function createOrderProcess(): ProcessDefinition
    {
        return ProcessDefinition::fromArray([
            'name' => 'Create Order',
            'slug' => 'create-order',
            'entryNodeId' => 'start_01',
            'nodes' => [
                ['id' => 'start_01', 'type' => 'start', 'position' => ['x' => -100, 'y' => 0], 'data' => ['label' => 'create-order guard']],
                ['id' => 'route_01', 'type' => 'route', 'position' => ['x' => 0, 'y' => 0], 'data' => [
                    'method' => 'POST',
                    'uri' => '/orders',
                    'name' => 'orders.store',
                    'middleware' => ['auth:sanctum'],
                ]],
                ['id' => 'request_01', 'type' => 'form_request', 'position' => ['x' => 100, 'y' => 0], 'data' => [
                    'class' => 'StoreOrderRequest',
                    'rules' => ['customer_id' => ['required', 'integer']],
                ]],
                ['id' => 'controller_01', 'type' => 'controller', 'position' => ['x' => 200, 'y' => 0], 'data' => [
                    'class' => 'OrderController',
                    'method' => 'store',
                ]],
                ['id' => 'action_01', 'type' => 'action', 'position' => ['x' => 300, 'y' => 0], 'data' => [
                    'class' => 'CreateOrderAction',
                ]],
                ['id' => 'model_01', 'type' => 'model_create', 'position' => ['x' => 400, 'y' => 0], 'data' => [
                    'model' => 'App\\Models\\Order',
                ]],
                ['id' => 'event_01', 'type' => 'event', 'position' => ['x' => 500, 'y' => 0], 'data' => [
                    'class' => 'OrderCreated',
                ]],
                ['id' => 'resource_01', 'type' => 'api_resource', 'position' => ['x' => 600, 'y' => 0], 'data' => [
                    'class' => 'OrderResource',
                ]],
                ['id' => 'response_01', 'type' => 'response', 'position' => ['x' => 700, 'y' => 0], 'data' => [
                    'status' => 201,
                ]],
                ['id' => 'end_01', 'type' => 'end', 'position' => ['x' => 800, 'y' => 0], 'data' => []],
            ],
            'edges' => [
                ['id' => 'e0', 'source' => 'start_01', 'target' => 'route_01'],
                ['id' => 'e1', 'source' => 'route_01', 'target' => 'request_01'],
                ['id' => 'e2', 'source' => 'request_01', 'target' => 'controller_01'],
                ['id' => 'e3', 'source' => 'controller_01', 'target' => 'action_01'],
                ['id' => 'e4', 'source' => 'action_01', 'target' => 'model_01'],
                ['id' => 'e5', 'source' => 'model_01', 'target' => 'event_01'],
                ['id' => 'e6', 'source' => 'event_01', 'target' => 'response_01'],
                ['id' => 'e7', 'source' => 'response_01', 'target' => 'end_01'],
            ],
        ]);
    }

    private function compiler(): ProcessCompiler
    {
        $renderer = new StubRenderer(__DIR__.'/../../../resources/stubs');
        $resolver = new ClassNameResolver();
        $repository = new FileProcessRepository($this->directory);
        $this->app->instance(ProcessRepository::class, $repository);

        $pipeline = new ValidationPipeline([
            new GraphStructureRule(),
            new AllowedConnectionsRule(),
            new RouteRule(),
            new RouteCollisionRule($repository),
            new ClassNameRule(),
            new FormRequestRule(),
        ]);

        return new ProcessCompiler(
            validationPipeline: $pipeline,
            routeFileCompiler: new RouteFileCompiler(),
            controllerCompiler: new ControllerCompiler($renderer, $resolver),
            formRequestCompiler: new FormRequestCompiler($renderer, $resolver),
            actionCompiler: new ActionCompiler($renderer, $resolver),
            eventCompiler: new EventCompiler($renderer, $resolver),
            jobCompiler: new JobCompiler($renderer, $resolver),
            apiResourceCompiler: new ApiResourceCompiler($renderer, $resolver),
            outputDirectories: [
                'routes' => base_path('routes/process-builder.php'),
                'controllers' => app_path('Http/Controllers/ProcessBuilder'),
                'requests' => app_path('Http/Requests/ProcessBuilder'),
                'actions' => app_path('Actions/ProcessBuilder'),
                'events' => app_path('Events/ProcessBuilder'),
                'jobs' => app_path('Jobs/ProcessBuilder'),
                'resources' => app_path('Http/Resources/ProcessBuilder'),
            ],
        );
    }

    public function test_it_compiles_a_valid_process_successfully(): void
    {
        $result = $this->compiler()->compile($this->createOrderProcess());

        $this->assertTrue($result->isSuccessful());
        $this->assertGreaterThan(0, $result->files->count());
    }

    public function test_it_generates_a_form_request_file(): void
    {
        $result = $this->compiler()->compile($this->createOrderProcess());

        $formRequest = $this->findFile($result->files->all(), 'form_request');

        $this->assertNotNull($formRequest);
        $this->assertStringContainsString('final class StoreOrderRequest extends FormRequest', $formRequest->contents);
        $this->assertStringContainsString("'customer_id' => ['required', 'integer']", $formRequest->contents);
    }

    public function test_it_generates_a_controller_file_referencing_the_action(): void
    {
        $result = $this->compiler()->compile($this->createOrderProcess());

        $controller = $this->findFile($result->files->all(), 'controller');

        $this->assertNotNull($controller);
        $this->assertStringContainsString('final class OrderController extends Controller', $controller->contents);
        $this->assertStringContainsString('use App\\Actions\\ProcessBuilder\\CreateOrderAction;', $controller->contents);
    }

    public function test_it_generates_an_action_file_with_the_model_create_call(): void
    {
        $result = $this->compiler()->compile($this->createOrderProcess());

        $action = $this->findFile($result->files->all(), 'action');

        $this->assertNotNull($action);
        $this->assertStringContainsString('final class CreateOrderAction', $action->contents);
        $this->assertStringContainsString('Order::query()->create($data)', $action->contents);
    }

    public function test_it_generates_a_routes_file(): void
    {
        $result = $this->compiler()->compile($this->createOrderProcess());

        $routes = $this->findFile($result->files->all(), 'routes');

        $this->assertNotNull($routes);
        $this->assertStringContainsString("Route::middleware(['auth:sanctum'])", $routes->contents);
        $this->assertStringContainsString("->post('/orders'", $routes->contents);
        $this->assertStringContainsString("->name('orders.store')", $routes->contents);
    }

    public function test_the_route_calls_the_controllers_actual_method_name(): void
    {
        $result = $this->compiler()->compile($this->createOrderProcess());

        $routes = $this->findFile($result->files->all(), 'routes');

        $this->assertNotNull($routes);
        $this->assertStringContainsString("[OrderController::class, 'store']", $routes->contents);
        $this->assertStringNotContainsString("'index'", $routes->contents);
    }

    public function test_every_generated_file_contains_the_managed_marker(): void
    {
        $result = $this->compiler()->compile($this->createOrderProcess());

        foreach ($result->files->all() as $file) {
            $this->assertStringContainsString('This file is managed by Laravel Process Builder.', $file->contents);
        }
    }

    public function test_compilation_is_deterministic(): void
    {
        $process = $this->createOrderProcess();

        $first = $this->compiler()->compile($process);
        $second = $this->compiler()->compile($process);

        $this->assertSame($first->files->toArray(), $second->files->toArray());
    }

    public function test_it_refuses_to_compile_an_invalid_process(): void
    {
        $process = ProcessDefinition::fromArray(['name' => 'Empty', 'slug' => 'empty']);

        $result = $this->compiler()->compile($process);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(0, $result->files->count());
    }

    public function test_generated_php_files_are_syntactically_valid(): void
    {
        $result = $this->compiler()->compile($this->createOrderProcess());

        foreach ($result->files->all() as $file) {
            if ($file->logicalType === 'routes') {
                continue;
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'pb_syntax_').'.php';
            file_put_contents($tempFile, $file->contents);

            $output = [];
            $exitCode = 0;
            exec('php -l '.escapeshellarg($tempFile), $output, $exitCode);
            unlink($tempFile);

            $this->assertSame(0, $exitCode, "Generated file [{$file->relativePath}] has a syntax error: ".implode("\n", $output));
        }
    }

    /**
     * @param  list<\MohamedZaki\LaravelProcessBuilder\DTO\GeneratedFile>  $files
     */
    private function findFile(array $files, string $logicalType): ?\MohamedZaki\LaravelProcessBuilder\DTO\GeneratedFile
    {
        foreach ($files as $file) {
            if ($file->logicalType === $logicalType) {
                return $file;
            }
        }

        return null;
    }
}
