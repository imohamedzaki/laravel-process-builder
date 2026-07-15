<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Compilation;

use MohamedZaki\LaravelProcessBuilder\Domain\Nodes\ProcessNode;

final class ClassNameResolver
{
    public function shortClassName(ProcessNode $node, string $fallbackPrefix): string
    {
        $class = $node->data['class'] ?? null;

        if (is_string($class) && $class !== '') {
            return str_contains($class, '\\') ? (string) substr((string) strrchr($class, '\\'), 1) : $class;
        }

        return $fallbackPrefix.ucfirst($this->slugToStudly($node->id));
    }

    public function namespace(ProcessNode $node, string $defaultNamespace): string
    {
        $namespace = $node->data['namespace'] ?? null;

        return is_string($namespace) && $namespace !== '' ? $namespace : $defaultNamespace;
    }

    private function slugToStudly(string $slug): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9]+/', ' ', $slug) ?? $slug;

        return str_replace(' ', '', ucwords($normalized));
    }
}
