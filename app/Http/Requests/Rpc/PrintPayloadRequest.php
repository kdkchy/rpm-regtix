<?php

namespace App\Http\Requests\Rpc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrintPayloadRequest extends FormRequest
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
            'print_type' => ['required', 'string', Rule::in(['pickup_sheet', 'power_of_attorney'])],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['required', 'integer', 'exists:registrations,id'],
            'representative_data' => ['required_if:print_type,power_of_attorney', 'array'],
            'representative_data.name' => ['required_if:print_type,power_of_attorney', 'string', 'max:255'],
            'representative_data.ktp_number' => ['required_if:print_type,power_of_attorney', 'string', 'max:255'],
            'representative_data.dob' => ['required_if:print_type,power_of_attorney', 'date'],
            'representative_data.address' => ['required_if:print_type,power_of_attorney', 'string'],
            'representative_data.phone' => ['required_if:print_type,power_of_attorney', 'string', 'max:20'],
            'representative_data.relationship' => ['required_if:print_type,power_of_attorney', 'string', 'max:255'],
            'event_id' => ['sometimes', 'nullable', 'integer', 'exists:events,id'], // Optional, akan menggunakan event_id dari user jika tidak ada
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
            'print_type.required' => 'Jenis cetakan wajib diisi.',
            'print_type.in' => 'Jenis cetakan harus pickup_sheet atau power_of_attorney.',
            'participant_ids.required' => 'Daftar peserta wajib diisi.',
            'participant_ids.array' => 'Daftar peserta harus berupa array.',
            'participant_ids.min' => 'Minimal 1 peserta harus dipilih.',
            'representative_data.required_if' => 'Data perwakilan wajib diisi untuk jenis cetakan power_of_attorney.',
        ];
    }
}







