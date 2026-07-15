<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Contracts;

use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationResult;

interface ValidationRule
{
    public function validate(ProcessDefinition $process): ValidationResult;
}
