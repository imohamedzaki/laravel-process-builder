<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Console;

use Illuminate\Console\Command;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;

final class ListProcessesCommand extends Command
{
    protected $signature = 'process-builder:list';

    protected $description = 'List all saved process definitions';

    public function handle(ProcessRepository $repository): int
    {
        $processes = $repository->all()->all();

        if ($processes === []) {
            $this->components->info('No process definitions found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Slug', 'Name', 'Status', 'Version', 'Nodes', 'Edges'],
            array_map(
                static fn (ProcessDefinition $process): array => [
                    $process->slug,
                    $process->name,
                    $process->status->value,
                    (string) $process->version,
                    (string) count($process->nodes),
                    (string) count($process->edges),
                ],
                $processes,
            ),
        );

        return self::SUCCESS;
    }
}
