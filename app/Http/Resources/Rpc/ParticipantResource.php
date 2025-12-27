<?php

namespace App\Http\Resources\Rpc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
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
            'name' => $this->full_name,
            'registration_code' => $this->registration_code,
            'ticket_category' => $this->categoryTicketType->category->name ?? null,
            'ticket_type' => $this->categoryTicketType->ticketType->name ?? null,
            'bib_number' => $this->reg_id,
            'jersey_size' => $this->jersey_size,
            'status' => $this->is_validated ? 'VALIDATED' : 'NOT_VALIDATED',
            'address' => $this->address,
            'phone' => $this->phone,
        ];
    }
}









