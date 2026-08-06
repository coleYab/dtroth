<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp,gif', 'max:2048'],
            'redirect_uris' => ['required', 'array', 'min:1', 'max:10'],
            'redirect_uris.*' => ['required', 'string', 'max:255', 'url:http,https'],
            'allowed_origins' => ['sometimes', 'array', 'max:10'],
            'allowed_origins.*' => ['required', 'string', 'max:255', 'url:http,https', $this->noPathRule()],
        ];
    }

    /**
     * Rule ensuring the value is an origin (scheme + host) without a path.
     *
     * @return \Closure(string, mixed, \Closure): void
     */
    private function noPathRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $path = (string) parse_url($value, PHP_URL_PATH);

            if ($path !== '' && $path !== '/') {
                $fail('The :attribute must be an origin without a path.');
            }
        };
    }
}
