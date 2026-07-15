<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Console;

use Illuminate\Console\Command;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Domain\Edges\ProcessEdge;
use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;

final class ShowProcessCommand extends Command
{
    protected $signature = 'process-builder:show {process : The process slug or id}';

    protected $description = 'Show the details of a single process definition';

    public function handle(ProcessRepository $repository): int
    {
        /** @var string $identifier */
        $identifier = $this->argument('process');

        $process = $repository->find($identifier);

        if ($process === null) {
            $this->components->error("No process found matching [{$identifier}].");

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Name', $process->name);
        $this->components->twoColumnDetail('Slug', $process->slug);
        $this->components->twoColumnDetail('Status', $process->status->value);
        $this->components->twoColumnDetail('Version', (string) $process->version);
        $this->components->twoColumnDetail('Entry node', $process->entryNodeId ?? '-');

        $this->newLine();
        $this->line('Nodes:');
        $this->table(
            ['Id', 'Type', 'Data'],
            array_map(
                static fn (ProcessNode $node): array => [
                    $node->id,
                    $node->type->value,
                    json_encode($node->data, JSON_UNESCAPED_SLASHES) ?: '{}',
                ],
                $process->nodes,
            ),
        );

        $this->line('Edges:');
        $this->table(
            ['Id', 'Source', 'Target', 'Label'],
            array_map(
                static fn (ProcessEdge $edge): array => [
                    $edge->id,
                    $edge->source,
                    $edge->target,
                    $edge->label ?? '-',
                ],
                $process->edges,
            ),
        );

        return self::SUCCESS;
    }
}
