<?php

namespace App\Http\Requests\Rpc;

use Illuminate\Foundation\Http\FormRequest;

class SearchParticipantRequest extends FormRequest
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
            'keyword' => ['required', 'string', 'min:2'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'], // Made optional - backend will auto-detect if null
            'status' => ['nullable', 'string', 'in:NOT_VALIDATED,VALIDATED'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'keyword.required' => 'Keyword pencarian wajib diisi.',
            'keyword.min' => 'Keyword minimal 2 karakter.',
            'event_id.exists' => 'Event tidak ditemukan.',
        ];
    }
}









