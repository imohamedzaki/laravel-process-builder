<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Validation;

use MohamedZaki\LaravelProcessBuilder\Contracts\ValidationRule;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationResult;

final class ValidationPipeline
{
    /**
     * @param  list<ValidationRule>  $rules
     */
    public function __construct(private readonly array $rules)
    {
    }

    public function validate(ProcessDefinition $process): ValidationResult
    {
        $result = ValidationResult::valid();

        foreach ($this->rules as $rule) {
            $result = $result->merge($rule->validate($process));
        }

        return $result;
    }
}
