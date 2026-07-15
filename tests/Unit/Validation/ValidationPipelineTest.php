<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Validation;

use MohamedZaki\LaravelProcessBuilder\Contracts\ValidationRule;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationError;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationResult;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use MohamedZaki\LaravelProcessBuilder\Validation\ValidationPipeline;

final class ValidationPipelineTest extends TestCase
{
    public function test_it_merges_results_from_multiple_rules(): void
    {
        $ruleA = new class () implements ValidationRule {
            public function validate(ProcessDefinition $process): ValidationResult
            {
                return ValidationResult::fromIssues([ValidationError::error('a.error', 'A failed.')]);
            }
        };

        $ruleB = new class () implements ValidationRule {
            public function validate(ProcessDefinition $process): ValidationResult
            {
                return ValidationResult::fromIssues([ValidationError::warning('b.warning', 'B warns.')]);
            }
        };

        $pipeline = new ValidationPipeline([$ruleA, $ruleB]);
        $process = ProcessDefinition::fromArray(['name' => 'X', 'slug' => 'x']);

        $result = $pipeline->validate($process);

        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->errors());
        $this->assertCount(1, $result->warnings());
    }

    public function test_an_empty_pipeline_is_valid(): void
    {
        $pipeline = new ValidationPipeline([]);
        $process = ProcessDefinition::fromArray(['name' => 'X', 'slug' => 'x']);

        $this->assertTrue($pipeline->validate($process)->isValid());
    }
}
