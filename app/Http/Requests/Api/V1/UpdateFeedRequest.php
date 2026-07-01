<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'min:3', 'max:255'],
            'url' => [
                'required', 'url', 'max:255',
                Rule::unique('feeds', 'url')
                    ->where('user_id', $this->user()?->id)
                    ->ignore($this->route('feed')),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
