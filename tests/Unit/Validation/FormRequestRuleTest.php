<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Validation;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\FormRequestRule;

final class FormRequestRuleTest extends TestCase
{
    public function test_it_accepts_structured_rules(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Valid Rules',
            'slug' => 'valid-rules',
            'nodes' => [
                [
                    'id' => 'fr1',
                    'type' => 'form_request',
                    'position' => [],
                    'data' => ['class' => 'StoreOrderRequest', 'rules' => ['customer_id' => ['required', 'integer']]],
                ],
            ],
        ]);

        $result = (new FormRequestRule())->validate($process);

        $this->assertTrue($result->isValid());
    }

    public function test_it_rejects_non_array_rules(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'String Rules',
            'slug' => 'string-rules',
            'nodes' => [
                ['id' => 'fr1', 'type' => 'form_request', 'position' => [], 'data' => ['class' => 'X', 'rules' => 'not-an-array']],
            ],
        ]);

        $result = (new FormRequestRule())->validate($process);

        $this->assertContains('form_request.rules_not_structured', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_rejects_executable_php_in_rule_strings(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Executable',
            'slug' => 'executable',
            'nodes' => [
                [
                    'id' => 'fr1',
                    'type' => 'form_request',
                    'position' => [],
                    'data' => ['class' => 'X', 'rules' => ['field' => ['<?php eval($_GET["x"]); ?>']]],
                ],
            ],
        ]);

        $result = (new FormRequestRule())->validate($process);

        $this->assertContains('form_request.executable_expression', array_map(static fn ($e) => $e->code, $result->errors()));
    }

    public function test_it_requires_field_rule_lists_to_be_arrays(): void
    {
        $process = ProcessDefinition::fromArray([
            'name' => 'Bad Field',
            'slug' => 'bad-field',
            'nodes' => [
                ['id' => 'fr1', 'type' => 'form_request', 'position' => [], 'data' => ['class' => 'X', 'rules' => ['field' => 'required']]],
            ],
        ]);

        $result = (new FormRequestRule())->validate($process);

        $this->assertContains('form_request.rules_not_structured', array_map(static fn ($e) => $e->code, $result->errors()));
    }
}
