<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Domain;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Enums\NodeType;
use MohamedZaki\LaravelProcessBuilder\Enums\ProcessStatus;
use MohamedZaki\LaravelProcessBuilder\Exceptions\InvalidProcessDefinitionException;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class ProcessDefinitionTest extends TestCase
{
    public function test_it_hydrates_from_a_minimal_payload(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Create Order',
            'slug' => 'create-order',
        ]);

        $this->assertSame('Create Order', $process->name);
        $this->assertSame('create-order', $process->slug);
        $this->assertSame(1, $process->version);
        $this->assertSame(ProcessStatus::Draft, $process->status);
        $this->assertSame([], $process->nodes);
        $this->assertSame([], $process->edges);
        $this->assertSame([], $process->participants);
        $this->assertNotSame('', $process->id);
    }

    public function test_it_requires_a_name(): void
    {
        $this->expectException(InvalidProcessDefinitionException::class);

        ProcessDefinition::fromArray(['slug' => 'no-name']);
    }

    public function test_it_requires_a_kebab_case_slug(): void
    {
        $this->expectException(InvalidProcessDefinitionException::class);

        ProcessDefinition::fromArray(['name' => 'Bad Slug', 'slug' => 'Not Kebab Case']);
    }

    public function test_it_hydrates_nodes_and_edges(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Create Order',
            'slug' => 'create-order',
            'entryNodeId' => 'route_01',
            'nodes' => [
                ['id' => 'route_01', 'type' => 'route', 'position' => ['x' => 0, 'y' => 0], 'data' => ['uri' => '/orders']],
                ['id' => 'controller_01', 'type' => 'controller', 'position' => ['x' => 100, 'y' => 0], 'data' => []],
            ],
            'edges' => [
                ['id' => 'edge_01', 'source' => 'route_01', 'target' => 'controller_01'],
            ],
        ]);

        $this->assertCount(2, $process->nodes);
        $this->assertCount(1, $process->edges);
        $this->assertSame(NodeType::Route, $process->nodes[0]->type);
        $this->assertSame('/orders', $process->nodes[0]->data['uri']);
        $this->assertNotNull($process->nodeById('route_01'));
        $this->assertNull($process->nodeById('missing'));
    }

    public function test_it_rejects_unknown_node_types(): void
    {
        $this->expectException(InvalidProcessDefinitionException::class);

        ProcessDefinition::fromArray([
            'name' => 'Bad Node',
            'slug' => 'bad-node',
            'nodes' => [
                ['id' => 'n1', 'type' => 'not_a_real_type', 'position' => [], 'data' => []],
            ],
        ]);
    }

    public function test_it_rejects_duplicate_node_ids(): void
    {
        $this->expectException(InvalidProcessDefinitionException::class);

        ProcessDefinition::fromArray([
            'name' => 'Dup',
            'slug' => 'dup',
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => []],
                ['id' => 'n1', 'type' => 'controller', 'position' => [], 'data' => []],
            ],
        ]);
    }

    public function test_it_rejects_edges_referencing_missing_nodes(): void
    {
        $this->expectException(InvalidProcessDefinitionException::class);

        ProcessDefinition::fromArray([
            'name' => 'Orphan Edge',
            'slug' => 'orphan-edge',
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => []],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'n1', 'target' => 'does-not-exist'],
            ],
        ]);
    }

    public function test_it_round_trips_through_to_array_and_from_array(): void
    {
        $original = ProcessDefinition::fromArray([
            'name' => 'Round Trip',
            'slug' => 'round-trip',
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => ['x' => 1, 'y' => 2], 'data' => ['uri' => '/x']],
            ],
        ]);

        $rehydrated = ProcessDefinition::fromArray($original->toArray());

        $this->assertSame($original->id, $rehydrated->id);
        $this->assertSame($original->slug, $rehydrated->slug);
        $this->assertEquals($original->nodes[0]->toArray(), $rehydrated->nodes[0]->toArray());
    }

    public function test_incrementing_version_bumps_version_and_preserves_identity(): void
    {
        $process = ProcessDefinition::fromArray(['name' => 'V', 'slug' => 'v']);

        $bumped = $process->withIncrementedVersion();

        $this->assertSame($process->id, $bumped->id);
        $this->assertSame($process->version + 1, $bumped->version);
        $this->assertSame($process->participants, $bumped->participants);
    }

    public function test_it_hydrates_with_no_lanes_key_for_backward_compatibility(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Legacy',
            'slug' => 'legacy',
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => []],
            ],
        ]);

        $this->assertSame([], $process->participants);
    }

    public function test_it_hydrates_lanes_and_round_trips_them(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Lanes',
            'slug' => 'lanes',
            'lanes' => [
                ['id' => 'lane_manager', 'name' => 'Manager', 'actorType' => 'human', 'order' => 0, 'color' => '#fff'],
                ['id' => 'lane_system', 'name' => 'System', 'actorType' => 'system', 'order' => 1, 'color' => null],
            ],
        ]);

        $this->assertCount(2, $process->participants);
        $this->assertSame('Manager', $process->participantById('lane_manager')?->name);
        $this->assertSame('manager', $process->participantById('lane_manager')?->guard);
        $this->assertSame('system', $process->participantById('lane_system')?->actorType);
        $this->assertNull($process->participantById('missing'));

        $rehydrated = ProcessDefinition::fromArray($process->toArray());

        $this->assertEquals(
            array_map(static fn ($participant) => $participant->toArray(), $process->participants),
            array_map(static fn ($participant) => $participant->toArray(), $rehydrated->participants),
        );
    }

    public function test_it_rejects_duplicate_lane_ids(): void
    {
        $this->expectException(InvalidProcessDefinitionException::class);

        ProcessDefinition::fromArray([
            'name' => 'Dup Lanes',
            'slug' => 'dup-lanes',
            'lanes' => [
                ['id' => 'lane_1', 'name' => 'A', 'actorType' => null, 'order' => 0, 'color' => null],
                ['id' => 'lane_1', 'name' => 'B', 'actorType' => null, 'order' => 1, 'color' => null],
            ],
        ]);
    }
}
