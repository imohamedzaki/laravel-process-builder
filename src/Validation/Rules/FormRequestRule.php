<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Validation\Rules;

use MohamedZaki\LaravelProcessBuilder\Contracts\ValidationRule;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationError;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationResult;
use MohamedZaki\LaravelProcessBuilder\Enums\NodeType;

final class FormRequestRule implements ValidationRule
{
    public function validate(ProcessDefinition $process): ValidationResult
    {
        $issues = [];

        foreach ($process->nodes as $node) {
            if ($node->type !== NodeType::FormRequest) {
                continue;
            }

            $rules = $node->data['rules'] ?? [];

            if (! is_array($rules)) {
                $issues[] = ValidationError::error(
                    'form_request.rules_not_structured',
                    'Form request rules must be structured data, not a raw string.',
                    nodeId: $node->id,
                    field: 'rules',
                );

                continue;
            }

            foreach ($rules as $field => $fieldRules) {
                if (! is_string($field) || $field === '') {
                    $issues[] = ValidationError::error(
                        'form_request.invalid_field_name',
                        'Each validation rule entry must be keyed by a field name.',
                        nodeId: $node->id,
                        field: 'rules',
                    );

                    continue;
                }

                if (! is_array($fieldRules)) {
                    $issues[] = ValidationError::error(
                        'form_request.rules_not_structured',
                        "Validation rules for field [{$field}] must be a list of rule strings.",
                        nodeId: $node->id,
                        field: 'rules',
                    );

                    continue;
                }

                foreach ($fieldRules as $rule) {
                    if (! is_string($rule)) {
                        $issues[] = ValidationError::error(
                            'form_request.rules_not_structured',
                            "Validation rules for field [{$field}] must be strings.",
                            nodeId: $node->id,
                            field: 'rules',
                        );

                        continue;
                    }

                    if (str_contains($rule, '<?php') || str_contains($rule, 'eval(')) {
                        $issues[] = ValidationError::error(
                            'form_request.executable_expression',
                            "Validation rule for field [{$field}] must not contain executable PHP expressions.",
                            nodeId: $node->id,
                            field: 'rules',
                        );
                    }
                }
            }

            $authorize = $node->data['authorize'] ?? null;

            if ($authorize !== null && ! is_bool($authorize) && ! is_string($authorize)) {
                $issues[] = ValidationError::error(
                    'form_request.invalid_authorization_mode',
                    'The form request authorization mode must be explicit (boolean or gate name).',
                    nodeId: $node->id,
                    field: 'authorize',
                );
            }
        }

        return ValidationResult::fromIssues($issues);
    }
}
