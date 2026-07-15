<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Console;

use Illuminate\Console\Command;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;

final class DemoCommand extends Command
{
    protected $signature = 'process-builder:demo {--force : Overwrite demo processes that already exist}';

    protected $description = 'Install bundled demo process definitions (Create Order, Approve Leave Request)';

    /**
     * @var list<string>
     */
    private const DEMOS = ['create-order', 'approve-leave-request'];

    public function handle(ProcessRepository $repository): int
    {
        foreach (self::DEMOS as $slug) {
            $this->installDemo($repository, $slug);
        }

        $this->newLine();
        $this->components->info('Demo processes installed. Open the dashboard or run `process-builder:show <slug>` to inspect them.');

        return self::SUCCESS;
    }

    private function installDemo(ProcessRepository $repository, string $slug): void
    {
        if ($repository->exists($slug) && ! $this->option('force')) {
            $this->components->warn("Skipped [{$slug}]: a process with this slug already exists. Use --force to overwrite.");

            return;
        }

        $path = __DIR__.'/../../resources/process-builder-demos/'.$slug.'.json';

        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->components->error("Unable to read bundled demo fixture for [{$slug}].");

            return;
        }

        /** @var mixed $payload */
        $payload = json_decode($contents, true);

        if (! is_array($payload)) {
            $this->components->error("Bundled demo fixture for [{$slug}] does not contain valid JSON.");

            return;
        }

        $process = ProcessDefinition::fromArray($payload);

        $this->components->task("Install [{$slug}]", function () use ($repository, $process): bool {
            $repository->save($process);

            return true;
        });
    }
}
