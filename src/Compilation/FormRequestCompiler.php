<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Compilation;

use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;

final class FormRequestCompiler
{
    public function __construct(
        private readonly StubRenderer $renderer,
        private readonly ClassNameResolver $resolver,
    ) {
    }

    public function compile(ProcessNode $node, string $defaultNamespace): string
    {
        $class = $this->resolver->shortClassName($node, '');
        $namespace = $this->resolver->namespace($node, $defaultNamespace);

        $authorize = $node->data['authorize'] ?? true;
        $authorizeExpression = is_bool($authorize) ? ($authorize ? 'true' : 'false') : 'true';

        /** @var array<string, list<string>> $rules */
        $rules = is_array($node->data['rules'] ?? null) ? $node->data['rules'] : [];

        return $this->renderer->render('form-request', [
            'namespace' => $namespace,
            'class' => $class,
            'authorize' => $authorizeExpression,
            'rules' => $this->exportRules($rules),
        ]);
    }

    /**
     * @param  array<string, list<string>>  $rules
     */
    private function exportRules(array $rules): string
    {
        if ($rules === []) {
            return '[]';
        }

        $lines = ['['];

        foreach ($rules as $field => $fieldRules) {
            $ruleList = implode(', ', array_map(
                static fn (string $rule): string => "'".addslashes($rule)."'",
                array_values($fieldRules),
            ));

            $lines[] = "            '".addslashes((string) $field)."' => [{$ruleList}],";
        }

        $lines[] = '        ]';

        return implode("\n", $lines);
    }
}
