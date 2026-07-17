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
            'participants' => ['array'],
            'participants.*.id' => ['required_with:participants', 'string'],
            'participants.*.name' => ['required_with:participants', 'string'],
            'participants.*.guard' => ['required_with:participants', 'string', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/'],
            'participants.*.actorType' => ['nullable', 'string', 'in:human,system'],
            'participants.*.order' => ['nullable', 'integer'],
            'participants.*.color' => ['nullable', 'string'],
        ];
    }
}
