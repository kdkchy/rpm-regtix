<?php

namespace App\Http\Resources;

use App\Models\CategoryTicketType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryTicketTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $categoryTicketType = CategoryTicketType::withCount(['registrations as paid_registrations_count' => function ($q) {
            $q->where('payment_status', 'paid');
        }])->find($this->pivot->id);

        $used = $categoryTicketType->paid_registrations_count ?? 0;


        return [
            'category_ticket_type_id'   => $this->pivot->id,
            'ticket_type_id'            => $this->id,
            'name'                      => $this->name,
            'price'                     => $this->pivot->price,
            'quota'                     => $this->pivot->quota,
            'used'                      => $used,
            'remaining'                 => $this->pivot->quota - $used,
            'valid_from'                => $this->valid_from,
            'valid_until'               => $this->valid_until,
        ];
    }
}
