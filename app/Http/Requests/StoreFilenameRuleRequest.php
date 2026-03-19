<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFilenameRuleRequest extends FormRequest
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
            'rule_mode' => ['required', 'string', Rule::in(['replace', 'remove_token'])],
            'pattern' => ['required', 'string', 'max:255'],
            'replacement' => ['nullable', 'string', 'max:255'],
            'is_regex' => ['required', 'boolean'],
            'is_case_sensitive' => ['required', 'boolean'],
            'whole_word' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $sortOrder = $this->input('sort_order');

        $this->merge([
            'replacement' => (string) ($this->input('replacement') ?? ''),
            'is_regex' => $this->boolean('is_regex'),
            'is_case_sensitive' => $this->boolean('is_case_sensitive'),
            'whole_word' => $this->boolean('whole_word', true),
            'is_active' => $this->boolean('is_active', true),
            'sort_order' => is_numeric($sortOrder) ? (int) $sortOrder : 100,
        ]);
    }
}
