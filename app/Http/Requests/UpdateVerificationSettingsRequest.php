<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVerificationSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'consent_text' => ['nullable', 'string', 'max:1000'],
            're_verify_months' => ['required', Rule::in([3, 6, 12])],
            'presets' => ['nullable', 'array'],
            'presets.*' => ['integer', 'exists:age_query_presets,id'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            're_verify_months.in' => 'Re-verification must occur every 3, 6, or 12 months.',
            'consent_text.max' => 'The consent text cannot be longer than 1000 characters.',
        ];
    }
}
