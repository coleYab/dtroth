<?php

namespace App\Http\Requests;

use App\Enums\EnvironmentName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiKeyRequest extends FormRequest
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
            'environment' => ['required', Rule::enum(EnvironmentName::class)],
            'name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Get the validated environment name.
     */
    public function environmentName(): EnvironmentName
    {
        return EnvironmentName::from($this->string('environment')->toString());
    }
}
