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
        if ($target === NodeType::Start || in_array($source, [NodeType::End, NodeType::Success, NodeType::Failure, NodeType::Exception], strict: true)) {
            return false;
        }

        if ($source === NodeType::Start) {
            return true;
        }

        if ($source === NodeType::Response) {
            return $target === NodeType::End;
        }

        if ($source !== NodeType::Condition) {
            return self::rank($source) <= self::rank($target);
        }

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

    private static function rank(NodeType $type): int
    {
        return match ($type) {
            NodeType::Start => 0,
            NodeType::Route => 10,
            NodeType::Middleware => 20,
            NodeType::FormRequest => 30,
            NodeType::Authorization => 35,
            NodeType::Controller => 40,
            NodeType::Action => 50,
            NodeType::Service => 55,
            NodeType::Transaction => 60,
            NodeType::Model, NodeType::ModelCreate, NodeType::ModelUpdate, NodeType::ModelDelete => 70,
            NodeType::Condition => 75,
            NodeType::Event => 80,
            NodeType::Job => 85,
            NodeType::Notification => 90,
            NodeType::ApiResource => 95,
            NodeType::Response => 100,
            NodeType::Success, NodeType::Failure, NodeType::Exception, NodeType::End => 110,
        };
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
