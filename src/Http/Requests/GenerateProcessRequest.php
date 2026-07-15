<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateProcessRequest extends FormRequest
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
            'previewToken' => ['required', 'string'],
            'force' => ['sometimes', 'boolean'],
        ];
    }
}
