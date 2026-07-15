<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Enums;

enum NodeType: string
{
    case Start = 'start';
    case Route = 'route';
    case Middleware = 'middleware';
    case FormRequest = 'form_request';
    case Authorization = 'authorization';
    case Controller = 'controller';
    case Action = 'action';
    case Service = 'service';
    case Transaction = 'transaction';
    case Condition = 'condition';
    case Model = 'model';
    case ModelCreate = 'model_create';
    case ModelUpdate = 'model_update';
    case ModelDelete = 'model_delete';
    case Event = 'event';
    case Job = 'job';
    case Notification = 'notification';
    case ApiResource = 'api_resource';
    case Response = 'response';
    case Success = 'success';
    case Failure = 'failure';
    case Exception = 'exception';
    case End = 'end';

    /**
     * Node types that are fully implemented (validated, compiled, generated) in the MVP.
     *
     * @return list<self>
     */
    public static function implemented(): array
    {
        return [
            self::Start,
            self::Route,
            self::Middleware,
            self::FormRequest,
            self::Controller,
            self::Action,
            self::Transaction,
            self::ModelCreate,
            self::ModelUpdate,
            self::Event,
            self::Job,
            self::ApiResource,
            self::Response,
            self::Condition,
            self::End,
        ];
    }

    public function isImplemented(): bool
    {
        return in_array($this, self::implemented(), strict: true);
    }
}
