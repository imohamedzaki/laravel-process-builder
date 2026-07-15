<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Validation;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\GraphStructureRule;

final class GraphStructureRuleTest extends TestCase
{
    public function test_it_reports_error_for_empty_process(): void
    {
        $process = ProcessDefinition::fromArray(['name' => 'Empty', 'slug' => 'empty']);

        $result = (new GraphStructureRule())->validate($process);

        $this->assertFalse($result->isValid());
        $this->assertSame('graph.empty', $result->errors()[0]->code);
    }

    public function test_it_requires_an_entry_node(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'No Entry',
            'slug' => 'no-entry',
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => []],
            ],
        ]);

        $result = (new GraphStructureRule())->validate($process);

        $this->assertContains('graph.no_entry_node', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_rejects_multiple_route_entry_nodes(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Two Routes',
            'slug' => 'two-routes',
            'entryNodeId' => 'r1',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => [], 'data' => []],
                ['id' => 'r2', 'type' => 'route', 'position' => [], 'data' => []],
            ],
        ]);

        $result = (new GraphStructureRule())->validate($process);

        $this->assertContains('graph.multiple_route_entries', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_warns_about_orphan_nodes(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Orphan',
            'slug' => 'orphan',
            'entryNodeId' => 'r1',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => [], 'data' => []],
                ['id' => 'c1', 'type' => 'controller', 'position' => [], 'data' => []],
                ['id' => 'orphan', 'type' => 'action', 'position' => [], 'data' => []],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'r1', 'target' => 'c1'],
            ],
        ]);

        $result = (new GraphStructureRule())->validate($process);

        $warningCodes = array_map(static fn ($w) => $w->code, $result->warnings());
        $this->assertContains('graph.orphan_node', $warningCodes);
    }

    public function test_it_detects_a_cycle(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Cyclic',
            'slug' => 'cyclic',
            'entryNodeId' => 'a',
            'nodes' => [
                ['id' => 'a', 'type' => 'action', 'position' => [], 'data' => []],
                ['id' => 'b', 'type' => 'action', 'position' => [], 'data' => []],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'a', 'target' => 'b'],
                ['id' => 'e2', 'source' => 'b', 'target' => 'a'],
            ],
        ]);

        $result = (new GraphStructureRule())->validate($process);

        $this->assertContains('graph.cycle_detected', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_warns_when_no_terminal_response_exists(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'No Terminal',
            'slug' => 'no-terminal',
            'entryNodeId' => 'r1',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => [], 'data' => []],
            ],
        ]);

        $result = (new GraphStructureRule())->validate($process);

        $this->assertContains('graph.no_terminal_response', array_map(static fn ($w) => $w->code, $result->warnings()));
    }

    public function test_a_well_formed_process_produces_no_errors(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Good',
            'slug' => 'good',
            'entryNodeId' => 'r1',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => [], 'data' => []],
                ['id' => 'c1', 'type' => 'controller', 'position' => [], 'data' => []],
                ['id' => 'resp', 'type' => 'response', 'position' => [], 'data' => []],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'r1', 'target' => 'c1'],
                ['id' => 'e2', 'source' => 'c1', 'target' => 'resp'],
            ],
        ]);

        $result = (new GraphStructureRule())->validate($process);

        $this->assertTrue($result->isValid());
    }
}
