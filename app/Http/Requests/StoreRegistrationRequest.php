<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
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
            'event_id' => 'required|integer|exists:events,id',
            'category_ticket_type_id' => 'required|integer|exists:category_ticket_type,id',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required','string','regex:/^\+[1-9]\d{7,14}$/',
            'gender' => 'required|in:Male,Female',
            'place_of_birth' => 'required|string|max:255',
            'dob' => 'required|date',
            'address' => 'required|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            'id_card_type' => 'required|in:KTP,SIM,PASSPORT,KARTU PELAJAR,KITAS,KITAP,OTHER',
            'id_card_number' => 'required|string|max:255',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required','string','regex:/^\+[1-9]\d{7,14}$/',
            'blood_type' => 'required|in:A,B,AB,O',
            'nationality' => 'required|string|max:255',
            'jersey_size' => 'required|string',
            'community_name' => 'nullable|string|max:255',
            'bib_name' => 'nullable|string|max:255',
            'voucher_code' => 'nullable|string|max:255',
        ];
    }
}
