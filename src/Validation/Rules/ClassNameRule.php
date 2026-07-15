<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Validation\Rules;

use MohamedZaki\LaravelProcessBuilder\Contracts\ValidationRule;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationError;
use MohamedZaki\LaravelProcessBuilder\DTO\ValidationResult;
use MohamedZaki\LaravelProcessBuilder\Enums\NodeType;

final class ClassNameRule implements ValidationRule
{
    private const RESERVED_KEYWORDS = [
        'class', 'interface', 'trait', 'enum', 'function', 'namespace', 'use',
        'public', 'private', 'protected', 'static', 'abstract', 'final',
        'if', 'else', 'foreach', 'for', 'while', 'switch', 'match', 'return',
        'array', 'string', 'int', 'float', 'bool', 'object', 'mixed', 'void', 'null', 'true', 'false',
    ];

    private const CLASS_BEARING_TYPES = [
        NodeType::Controller,
        NodeType::Action,
        NodeType::Service,
        NodeType::FormRequest,
        NodeType::Event,
        NodeType::Job,
        NodeType::Notification,
        NodeType::ApiResource,
    ];

    public function validate(ProcessDefinition $process): ValidationResult
    {
        $issues = [];

        foreach ($process->nodes as $node) {
            if (! in_array($node->type, self::CLASS_BEARING_TYPES, strict: true)) {
                continue;
            }

            $class = $node->data['class'] ?? null;

            if (! is_string($class) || $class === '') {
                $issues[] = ValidationError::error(
                    'class.missing',
                    "Node [{$node->id}] must specify a class name.",
                    nodeId: $node->id,
                    field: 'class',
                );

                continue;
            }

            $shortName = str_contains($class, '\\') ? substr((string) strrchr($class, '\\'), 1) : $class;

            if (! preg_match('/^[A-Z][A-Za-z0-9_]*$/', $shortName)) {
                $issues[] = ValidationError::error(
                    'class.invalid_name',
                    "Class name [{$class}] must be a valid StudlyCase PHP class name.",
                    nodeId: $node->id,
                    field: 'class',
                );
            } elseif (in_array(strtolower($shortName), self::RESERVED_KEYWORDS, strict: true)) {
                $issues[] = ValidationError::error(
                    'class.reserved_keyword',
                    "Class name [{$class}] uses a reserved PHP keyword.",
                    nodeId: $node->id,
                    field: 'class',
                );
            }

            $namespace = $node->data['namespace'] ?? null;

            if ($namespace !== null && (! is_string($namespace) || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $namespace))) {
                $issues[] = ValidationError::error(
                    'class.invalid_namespace',
                    "Namespace [{$namespace}] is not a valid PHP namespace.",
                    nodeId: $node->id,
                    field: 'namespace',
                );
            }
        }

        return ValidationResult::fromIssues($issues);
    }
}
