<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Validation;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\ParticipantReferenceRule;

final class LaneReferenceRuleTest extends TestCase
{
    public function test_it_requires_a_participant(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'No Lanes',
            'slug' => 'no-lanes',
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => ['laneId' => 'anything']],
            ],
        ]);

        $result = (new ParticipantReferenceRule())->validate($process);

        $this->assertFalse($result->isValid());
        $this->assertSame('participant.required', $result->errors()[0]->code);
    }

    public function test_it_is_valid_when_node_has_no_lane_assignment(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Unassigned',
            'slug' => 'unassigned',
            'participants' => [
                ['id' => 'participant_manager', 'name' => 'Manager', 'guard' => 'manager', 'actorType' => 'human', 'order' => 0, 'color' => null],
            ],
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => []],
            ],
        ]);

        $result = (new ParticipantReferenceRule())->validate($process);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->warnings());
    }

    public function test_it_is_valid_when_node_references_a_known_lane(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Valid Lane',
            'slug' => 'valid-lane',
            'participants' => [
                ['id' => 'participant_manager', 'name' => 'Manager', 'guard' => 'manager', 'actorType' => 'human', 'order' => 0, 'color' => null],
                ['id' => 'participant_system', 'name' => 'System', 'guard' => 'system', 'actorType' => 'system', 'order' => 1, 'color' => null],
            ],
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => ['participantId' => 'participant_manager']],
            ],
        ]);

        $result = (new ParticipantReferenceRule())->validate($process);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->warnings());
    }

    public function test_it_errors_on_an_unknown_participant_reference(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Bad Lane',
            'slug' => 'bad-lane',
            'participants' => [
                ['id' => 'participant_manager', 'name' => 'Manager', 'guard' => 'manager', 'actorType' => 'human', 'order' => 0, 'color' => null],
                ['id' => 'participant_system', 'name' => 'System', 'guard' => 'system', 'actorType' => 'system', 'order' => 1, 'color' => null],
            ],
            'nodes' => [
                ['id' => 'n1', 'type' => 'route', 'position' => [], 'data' => ['participantId' => 'does-not-exist']],
            ],
        ]);

        $result = (new ParticipantReferenceRule())->validate($process);

        $this->assertFalse($result->isValid());
        $this->assertSame('participant.unassigned_node', $result->errors()[0]->code);
        $this->assertSame('n1', $result->errors()[0]->nodeId);
    }
}
