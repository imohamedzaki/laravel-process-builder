<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Validation;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\AllowedConnectionsRule;

final class AllowedConnectionsRuleTest extends TestCase
{
    public function test_it_allows_a_valid_connection(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Valid',
            'slug' => 'valid',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => [], 'data' => []],
                ['id' => 'c1', 'type' => 'controller', 'position' => [], 'data' => []],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'r1', 'target' => 'c1'],
            ],
        ]);

        $result = (new AllowedConnectionsRule())->validate($process);

        $this->assertTrue($result->isValid());
    }

    public function test_it_rejects_a_connection_out_of_a_terminal_node(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Invalid',
            'slug' => 'invalid',
            'nodes' => [
                ['id' => 'r1', 'type' => 'end', 'position' => [], 'data' => []],
                ['id' => 'job1', 'type' => 'job', 'position' => [], 'data' => []],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'r1', 'target' => 'job1'],
            ],
        ]);

        $result = (new AllowedConnectionsRule())->validate($process);

        $this->assertFalse($result->isValid());
        $this->assertSame('graph.invalid_connection', $result->errors()[0]->code);
    }

    public function test_it_requires_the_correct_handle_for_condition_branches(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Condition',
            'slug' => 'condition',
            'nodes' => [
                ['id' => 'cond', 'type' => 'condition', 'position' => [], 'data' => []],
                ['id' => 'end1', 'type' => 'end', 'position' => [], 'data' => []],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'cond', 'sourceHandle' => 'success', 'target' => 'end1'],
            ],
        ]);

        $result = (new AllowedConnectionsRule())->validate($process);

        $this->assertFalse($result->isValid());
    }

    public function test_it_allows_the_failure_branch_to_reach_end(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Condition Failure',
            'slug' => 'condition-failure',
            'nodes' => [
                ['id' => 'cond', 'type' => 'condition', 'position' => [], 'data' => []],
                ['id' => 'end1', 'type' => 'end', 'position' => [], 'data' => []],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'cond', 'sourceHandle' => 'failure', 'target' => 'end1'],
            ],
        ]);

        $result = (new AllowedConnectionsRule())->validate($process);

        $this->assertTrue($result->isValid());
    }
}
