<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Validation;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\ClassNameRule;

final class ClassNameRuleTest extends TestCase
{
    public function test_it_accepts_a_valid_class_name(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Valid Class',
            'slug' => 'valid-class',
            'nodes' => [
                ['id' => 'a1', 'type' => 'action', 'position' => [], 'data' => ['class' => 'CreateOrderAction']],
            ],
        ]);

        $result = (new ClassNameRule())->validate($process);

        $this->assertTrue($result->isValid());
    }

    public function test_it_requires_a_class_name(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Missing Class',
            'slug' => 'missing-class',
            'nodes' => [
                ['id' => 'a1', 'type' => 'action', 'position' => [], 'data' => []],
            ],
        ]);

        $result = (new ClassNameRule())->validate($process);

        $this->assertContains('class.missing', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_rejects_a_lowercase_class_name(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Lowercase',
            'slug' => 'lowercase',
            'nodes' => [
                ['id' => 'a1', 'type' => 'action', 'position' => [], 'data' => ['class' => 'createOrderAction']],
            ],
        ]);

        $result = (new ClassNameRule())->validate($process);

        $this->assertContains('class.invalid_name', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_rejects_a_reserved_keyword(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Reserved',
            'slug' => 'reserved',
            'nodes' => [
                ['id' => 'a1', 'type' => 'action', 'position' => [], 'data' => ['class' => 'Class']],
            ],
        ]);

        $result = (new ClassNameRule())->validate($process);

        $this->assertContains('class.reserved_keyword', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_rejects_an_invalid_namespace(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Bad Namespace',
            'slug' => 'bad-namespace',
            'nodes' => [
                ['id' => 'a1', 'type' => 'action', 'position' => [], 'data' => ['class' => 'MyAction', 'namespace' => 'App\\123Invalid']],
            ],
        ]);

        $result = (new ClassNameRule())->validate($process);

        $this->assertContains('class.invalid_namespace', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_accepts_a_fully_qualified_class_name(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'FQCN',
            'slug' => 'fqcn',
            'nodes' => [
                ['id' => 'a1', 'type' => 'action', 'position' => [], 'data' => ['class' => 'App\\Actions\\CreateOrderAction']],
            ],
        ]);

        $result = (new ClassNameRule())->validate($process);

        $this->assertTrue($result->isValid());
    }
}
