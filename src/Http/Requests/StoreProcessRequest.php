<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'guard' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'entryNodeId' => ['nullable', 'string'],
            'nodes' => ['array'],
            'nodes.*.id' => ['required_with:nodes', 'string'],
            'nodes.*.type' => ['required_with:nodes', 'string'],
            'nodes.*.position' => ['array'],
            'nodes.*.data' => ['array'],
            'edges' => ['array'],
            'edges.*.id' => ['required_with:edges', 'string'],
            'edges.*.source' => ['required_with:edges', 'string'],
            'edges.*.target' => ['required_with:edges', 'string'],
            'lanes' => ['array'],
            'lanes.*.id' => ['required_with:lanes', 'string'],
            'lanes.*.name' => ['required_with:lanes', 'string'],
            'lanes.*.actorType' => ['nullable', 'string', 'in:human,system'],
            'lanes.*.order' => ['nullable', 'integer'],
            'lanes.*.color' => ['nullable', 'string'],
        ];
    }
}
