<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVerificationFlowRequest extends FormRequest
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
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'brand_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'success_redirect_url' => ['nullable', 'string', 'url:http,https', 'max:2048'],
            'failure_redirect_url' => ['nullable', 'string', 'url:http,https', 'max:2048'],
            'callback_url' => ['nullable', 'string', 'url:http,https', 'max:2048'],
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
            'brand_color.regex' => 'The brand color must be a valid hex color, e.g. #0f172a.',
            'logo.image' => 'The logo must be an image.',
            'logo.max' => 'The logo must not be larger than 2MB.',
        ];
    }
}
