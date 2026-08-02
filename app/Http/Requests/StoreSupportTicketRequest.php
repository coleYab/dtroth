<?php

namespace App\Http\Requests;

use App\Enums\SupportTicketCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
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
            'subject' => ['required', 'string', 'min:5', 'max:150'],
            'category' => ['required', 'string', Rule::enum(SupportTicketCategory::class)],
            'app_id' => ['nullable', 'integer', Rule::exists('apps', 'id')->where('user_id', $this->user()->id)],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:png,jpg,jpeg,webp,gif,pdf'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.min' => 'Please describe the issue in at least 20 characters.',
            'attachment.mimes' => 'Attachments must be an image (PNG, JPG, WEBP, GIF) or PDF.',
            'attachment.max' => 'Attachments must be 5 MB or smaller.',
        ];
    }
}
