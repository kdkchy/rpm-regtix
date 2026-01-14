<?php

namespace App\Http\Resources;

use App\Filament\Resources\VoucherCodeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'category_ticket_type_id' => $this->category_ticket_type_id,
            'voucher_code_id' => $this->voucher_code_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'place_of_birth' => $this->place_of_birth,
            'dob' => $this->dob,
            'address' => $this->address,
            'district' => $this->district,
            'province' => $this->province,
            'country' => $this->country,
            'id_card_type' => $this->id_card_type,
            'id_card_number' => $this->id_card_number,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'blood_type' => $this->blood_type,
            'nationality' => $this->nationality,
            'jersey_size' => $this->jersey_size,
            'community_name' => $this->community_name,
            'bib_name' => $this->bib_name,
            'voucher_code' => $this->whenLoaded('voucherCode', function () {
                return [
                    'id' => $this->voucherCode->id,
                    'code' => $this->voucherCode->code,
                    'used' => $this->voucherCode->used,
                    'voucher' => [
                        'id' => $this->voucherCode->voucher->id,
                        'name' => $this->voucherCode->voucher->name,
                        'final_price' => $this->voucherCode->voucher->final_price,
                    ],
                ];
            }),


            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
