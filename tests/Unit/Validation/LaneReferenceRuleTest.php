<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Validation;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\LaneReferenceRule;

final class LaneReferenceRuleTest extends TestCase
{
    public function test_it_is_valid_when_process_has_no_lanes(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'No Lanes',
            'slug' => 'no-lanes',
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => ['laneId' => 'anything']],
            ],
        ]);

        $result = (new LaneReferenceRule())->validate($process);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->warnings());
    }

    public function test_it_is_valid_when_node_has_no_lane_assignment(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Unassigned',
            'slug' => 'unassigned',
            'lanes' => [
                ['id' => 'lane_1', 'name' => 'Manager', 'actorType' => 'human', 'order' => 0, 'color' => null],
            ],
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => []],
            ],
        ]);

        $result = (new LaneReferenceRule())->validate($process);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->warnings());
    }

    public function test_it_is_valid_when_node_references_a_known_lane(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Valid Lane',
            'slug' => 'valid-lane',
            'lanes' => [
                ['id' => 'lane_1', 'name' => 'Manager', 'actorType' => 'human', 'order' => 0, 'color' => null],
            ],
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => ['laneId' => 'lane_1']],
            ],
        ]);

        $result = (new LaneReferenceRule())->validate($process);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->warnings());
    }

    public function test_it_warns_but_does_not_error_on_an_unknown_lane_reference(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Bad Lane',
            'slug' => 'bad-lane',
            'lanes' => [
                ['id' => 'lane_1', 'name' => 'Manager', 'actorType' => 'human', 'order' => 0, 'color' => null],
            ],
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => ['laneId' => 'does-not-exist']],
            ],
        ]);

        $result = (new LaneReferenceRule())->validate($process);

        $this->assertTrue($result->isValid());
        $this->assertCount(1, $result->warnings());
        $this->assertSame('lane.unknown_reference', $result->warnings()[0]->code);
        $this->assertSame('n1', $result->warnings()[0]->nodeId);
    }
}
