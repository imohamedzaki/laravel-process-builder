<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Compilation;

use MohamedZaki\LaravelProcessBuilder\Domain\Compilation\CompilationContext;
use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\CompilationResult;
use MohamedZaki\LaravelProcessBuilder\DTO\GeneratedFile;
use MohamedZaki\LaravelProcessBuilder\DTO\GeneratedFileCollection;
use MohamedZaki\LaravelProcessBuilder\Enums\NodeType;
use MohamedZaki\LaravelProcessBuilder\Graph\ProcessGraph;
use MohamedZaki\LaravelProcessBuilder\Validation\ValidationPipeline;

final class ProcessCompiler
{
    /**
     * @param  array<string, string>  $outputDirectories  logical type => absolute directory
     */
    public function __construct(
        private readonly ValidationPipeline $validationPipeline,
        private readonly RouteFileCompiler $routeFileCompiler,
        private readonly ControllerCompiler $controllerCompiler,
        private readonly FormRequestCompiler $formRequestCompiler,
        private readonly ActionCompiler $actionCompiler,
        private readonly EventCompiler $eventCompiler,
        private readonly JobCompiler $jobCompiler,
        private readonly ApiResourceCompiler $apiResourceCompiler,
        private readonly array $outputDirectories,
    ) {
    }

    public function compile(ProcessDefinition $process): CompilationResult
    {
        $validation = $this->validationPipeline->validate($process);

        if (! $validation->isValid()) {
            return new CompilationResult(new GeneratedFileCollection([]), $validation);
        }

        $context = new CompilationContext($process);
        $graph = new ProcessGraph($process);

        $files = [];

        /** @var array<string, array{class: string, method: string}> $routeControllerActions */
        $routeControllerActions = [];

        foreach ($process->nodes as $node) {
            match ($node->type) {
                NodeType::FormRequest => $files[] = $this->compileFormRequest($node),
                NodeType::Event => $files[] = $this->compileEvent($node, $process, $graph),
                NodeType::Job => $files[] = $this->compileJob($node, $process, $graph),
                NodeType::ApiResource => $files[] = $this->compileApiResource($node),
                NodeType::Action => $files[] = $this->compileAction($node, $process, $graph),
                default => null,
            };
        }

        /** @var array<string, array{class: string, method: string}> $compiledControllers  controller node id => class + method */
        $compiledControllers = [];

        foreach ($process->nodes as $node) {
            if ($node->type !== NodeType::Controller) {
                continue;
            }

            [$file, $fqcn] = $this->compileController($node, $process, $graph);
            $files[] = $file;

            $method = is_string($node->data['method'] ?? null) && $node->data['method'] !== '' ? $node->data['method'] : 'store';

            $compiledControllers[$node->id] = ['class' => $fqcn, 'method' => $method];
        }

        foreach ($process->nodes as $node) {
            if ($node->type !== NodeType::Route) {
                continue;
            }

            foreach ($graph->reachableFrom($node->id) as $reachableId => $_) {
                if (isset($compiledControllers[$reachableId])) {
                    $routeControllerActions[$node->id] = $compiledControllers[$reachableId];

                    break;
                }
            }
        }

        $routesFile = $this->routeFileCompiler->compile($context, $routeControllerActions);

        $files[] = new GeneratedFile(
            logicalType: 'routes',
            relativePath: 'routes/process-builder.php',
            absolutePath: $this->outputDirectories['routes'] ?? 'routes/process-builder.php',
            contents: $routesFile,
        );

        return new CompilationResult(new GeneratedFileCollection(array_values(array_filter($files))), $validation);
    }

    private function compileFormRequest(ProcessNode $node): GeneratedFile
    {
        $namespace = $this->outputNamespace('requests', 'App\\Http\\Requests\\ProcessBuilder');
        $contents = $this->formRequestCompiler->compile($node, $namespace);
        $class = $this->extractClassName($contents);

        return $this->toFile('form_request', 'requests', $class, $contents);
    }

    /**
     * @return array{0: GeneratedFile, 1: string}
     */
    private function compileController(ProcessNode $node, ProcessDefinition $process, ProcessGraph $graph): array
    {
        $namespace = $this->outputNamespace('controllers', 'App\\Http\\Controllers\\ProcessBuilder');

        $formRequestClass = $this->findConnectedClassName($node, $process, $graph, NodeType::FormRequest, 'requests', 'App\\Http\\Requests\\ProcessBuilder', reverse: true);
        $actionClass = $this->findConnectedClassName($node, $process, $graph, NodeType::Action, 'actions', 'App\\Actions\\ProcessBuilder');
        $resourceClass = $this->findConnectedClassName($node, $process, $graph, NodeType::ApiResource, 'resources', 'App\\Http\\Resources\\ProcessBuilder');

        $contents = $this->controllerCompiler->compile($node, $namespace, $formRequestClass, $actionClass, $resourceClass);
        $class = $this->extractClassName($contents);

        return [$this->toFile('controller', 'controllers', $class, $contents), $namespace.'\\'.$class];
    }

    private function compileAction(ProcessNode $node, ProcessDefinition $process, ProcessGraph $graph): GeneratedFile
    {
        $namespace = $this->outputNamespace('actions', 'App\\Actions\\ProcessBuilder');

        $modelClass = null;
        $transactional = (bool) ($node->data['transactionEnabled'] ?? false);

        foreach ($graph->outgoingEdges($node->id) as $edge) {
            $target = $process->nodeById($edge->target);

            if ($target !== null && in_array($target->type, [NodeType::ModelCreate, NodeType::ModelUpdate], strict: true)) {
                $model = $target->data['model'] ?? null;
                $modelClass = is_string($model) && $model !== '' ? $model : null;
            }

            if ($target !== null && $target->type === NodeType::Transaction) {
                $transactional = true;
            }
        }

        $bodyStatements = $modelClass !== null
            ? ["        return {$this->shortName($modelClass)}::query()->create(\$data);"]
            : [];

        $contents = $this->actionCompiler->compile($node, $namespace, $modelClass, $bodyStatements, $transactional);
        $class = $this->extractClassName($contents);

        return $this->toFile('action', 'actions', $class, $contents);
    }

    private function compileEvent(ProcessNode $node, ProcessDefinition $process, ProcessGraph $graph): GeneratedFile
    {
        $namespace = $this->outputNamespace('events', 'App\\Events\\ProcessBuilder');
        $modelClass = $this->connectedModelClass($node, $process, $graph);

        $contents = $this->eventCompiler->compile($node, $namespace, $modelClass);
        $class = $this->extractClassName($contents);

        return $this->toFile('event', 'events', $class, $contents);
    }

    private function compileJob(ProcessNode $node, ProcessDefinition $process, ProcessGraph $graph): GeneratedFile
    {
        $namespace = $this->outputNamespace('jobs', 'App\\Jobs\\ProcessBuilder');
        $modelClass = $this->connectedModelClass($node, $process, $graph);

        $contents = $this->jobCompiler->compile($node, $namespace, $modelClass);
        $class = $this->extractClassName($contents);

        return $this->toFile('job', 'jobs', $class, $contents);
    }

    private function compileApiResource(ProcessNode $node): GeneratedFile
    {
        $namespace = $this->outputNamespace('resources', 'App\\Http\\Resources\\ProcessBuilder');
        $contents = $this->apiResourceCompiler->compile($node, $namespace);
        $class = $this->extractClassName($contents);

        return $this->toFile('api_resource', 'resources', $class, $contents);
    }

    private function connectedModelClass(ProcessNode $node, ProcessDefinition $process, ProcessGraph $graph): ?string
    {
        foreach ($graph->incomingEdges($node->id) as $edge) {
            $source = $process->nodeById($edge->source);

            if ($source !== null && in_array($source->type, [NodeType::ModelCreate, NodeType::ModelUpdate], strict: true)) {
                $model = $source->data['model'] ?? null;

                return is_string($model) && $model !== '' ? $model : null;
            }
        }

        return null;
    }

    private function findConnectedClassName(
        ProcessNode $node,
        ProcessDefinition $process,
        ProcessGraph $graph,
        NodeType $type,
        string $outputKey,
        string $defaultNamespace,
        bool $reverse = false,
    ): ?string {
        $edges = $reverse ? $graph->incomingEdges($node->id) : $graph->outgoingEdges($node->id);

        foreach ($edges as $edge) {
            $candidateId = $reverse ? $edge->source : $edge->target;
            $candidate = $process->nodeById($candidateId);

            if ($candidate !== null && $candidate->type === $type) {
                $resolver = new ClassNameResolver();
                $namespace = $this->outputNamespace($outputKey, $defaultNamespace);

                return $namespace.'\\'.$resolver->shortClassName($candidate, '');
            }
        }

        return null;
    }

    private function toFile(string $logicalType, string $outputKey, string $class, string $contents): GeneratedFile
    {
        $directory = $this->outputDirectories[$outputKey] ?? '';
        $absolutePath = rtrim($directory, '/\\').DIRECTORY_SEPARATOR.$class.'.php';

        return new GeneratedFile(
            logicalType: $logicalType,
            relativePath: $this->toProjectRelativePath($absolutePath),
            absolutePath: $absolutePath,
            contents: $contents,
        );
    }

    private function toProjectRelativePath(string $absolutePath): string
    {
        $basePath = $this->outputDirectories['basePath'] ?? '';
        $normalizedAbsolute = str_replace('\\', '/', $absolutePath);

        if ($basePath === '' || ! str_starts_with($normalizedAbsolute, $basePath)) {
            return $normalizedAbsolute;
        }

        return ltrim(substr($normalizedAbsolute, strlen($basePath)), '/');
    }

    private function outputNamespace(string $outputKey, string $default): string
    {
        return $default;
    }

    private function extractClassName(string $contents): string
    {
        if (preg_match('/\bclass\s+(\w+)/', $contents, $matches) === 1) {
            return $matches[1];
        }

        throw new \RuntimeException('Unable to determine the generated class name.');
    }

    private function shortName(string $fqcn): string
    {
        return str_contains($fqcn, '\\') ? (string) substr((string) strrchr($fqcn, '\\'), 1) : $fqcn;
    }
}
