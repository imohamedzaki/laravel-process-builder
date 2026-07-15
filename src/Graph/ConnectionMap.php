<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Graph;

use MohamedZaki\LaravelProcessBuilder\Enums\NodeType;

final class ConnectionMap
{
    /**
     * @var list<array{source: NodeType, sourceHandle: ?string, target: NodeType}>
     */
    private static array $rules;

    public static function isAllowed(NodeType $source, NodeType $target, ?string $sourceHandle = null): bool
    {
        foreach (self::rules() as $rule) {
            if ($rule['source'] !== $source || $rule['target'] !== $target) {
                continue;
            }

            if ($rule['sourceHandle'] !== null && $rule['sourceHandle'] !== $sourceHandle) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @return list<array{source: NodeType, sourceHandle: ?string, target: NodeType}>
     */
    private static function rules(): array
    {
        return self::$rules ??= [
            ['source' => NodeType::Route, 'sourceHandle' => null, 'target' => NodeType::Middleware],
            ['source' => NodeType::Route, 'sourceHandle' => null, 'target' => NodeType::FormRequest],
            ['source' => NodeType::Route, 'sourceHandle' => null, 'target' => NodeType::Controller],

            ['source' => NodeType::Middleware, 'sourceHandle' => null, 'target' => NodeType::FormRequest],
            ['source' => NodeType::Middleware, 'sourceHandle' => null, 'target' => NodeType::Controller],

            ['source' => NodeType::FormRequest, 'sourceHandle' => null, 'target' => NodeType::Controller],

            ['source' => NodeType::Controller, 'sourceHandle' => null, 'target' => NodeType::Action],
            ['source' => NodeType::Controller, 'sourceHandle' => null, 'target' => NodeType::Response],

            ['source' => NodeType::Action, 'sourceHandle' => null, 'target' => NodeType::Transaction],
            ['source' => NodeType::Action, 'sourceHandle' => null, 'target' => NodeType::ModelCreate],
            ['source' => NodeType::Action, 'sourceHandle' => null, 'target' => NodeType::ModelUpdate],
            ['source' => NodeType::Action, 'sourceHandle' => null, 'target' => NodeType::Condition],
            ['source' => NodeType::Action, 'sourceHandle' => null, 'target' => NodeType::Event],
            ['source' => NodeType::Action, 'sourceHandle' => null, 'target' => NodeType::Job],
            ['source' => NodeType::Action, 'sourceHandle' => null, 'target' => NodeType::Response],

            ['source' => NodeType::Transaction, 'sourceHandle' => null, 'target' => NodeType::ModelCreate],
            ['source' => NodeType::Transaction, 'sourceHandle' => null, 'target' => NodeType::ModelUpdate],

            ['source' => NodeType::ModelCreate, 'sourceHandle' => null, 'target' => NodeType::Event],
            ['source' => NodeType::ModelCreate, 'sourceHandle' => null, 'target' => NodeType::Job],
            ['source' => NodeType::ModelCreate, 'sourceHandle' => null, 'target' => NodeType::Response],
            ['source' => NodeType::ModelCreate, 'sourceHandle' => null, 'target' => NodeType::Condition],

            ['source' => NodeType::ModelUpdate, 'sourceHandle' => null, 'target' => NodeType::Event],
            ['source' => NodeType::ModelUpdate, 'sourceHandle' => null, 'target' => NodeType::Job],
            ['source' => NodeType::ModelUpdate, 'sourceHandle' => null, 'target' => NodeType::Response],
            ['source' => NodeType::ModelUpdate, 'sourceHandle' => null, 'target' => NodeType::Condition],

            ['source' => NodeType::Condition, 'sourceHandle' => 'success', 'target' => NodeType::Action],
            ['source' => NodeType::Condition, 'sourceHandle' => 'success', 'target' => NodeType::Event],
            ['source' => NodeType::Condition, 'sourceHandle' => 'success', 'target' => NodeType::Job],
            ['source' => NodeType::Condition, 'sourceHandle' => 'success', 'target' => NodeType::Response],

            ['source' => NodeType::Condition, 'sourceHandle' => 'failure', 'target' => NodeType::Action],
            ['source' => NodeType::Condition, 'sourceHandle' => 'failure', 'target' => NodeType::Response],
            ['source' => NodeType::Condition, 'sourceHandle' => 'failure', 'target' => NodeType::End],

            ['source' => NodeType::Event, 'sourceHandle' => null, 'target' => NodeType::Job],
            ['source' => NodeType::Event, 'sourceHandle' => null, 'target' => NodeType::Response],

            ['source' => NodeType::Job, 'sourceHandle' => null, 'target' => NodeType::Response],
            ['source' => NodeType::Response, 'sourceHandle' => null, 'target' => NodeType::End],
        ];
    }
}
