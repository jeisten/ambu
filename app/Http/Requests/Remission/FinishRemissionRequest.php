<?php

namespace App\Http\Requests\Remission;

use Illuminate\Foundation\Http\FormRequest;

class FinishRemissionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'observations_closing' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the closing note or observation string.
     */
    public function getClosingNote(): ?string
    {
        return $this->input('notes') ?? $this->input('observations_closing');
    }
}
