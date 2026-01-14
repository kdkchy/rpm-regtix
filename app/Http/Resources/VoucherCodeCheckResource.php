<?php

namespace App\Http\Resources;

use App\Models\CategoryTicketType;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherCodeCheckResource extends JsonResource
{
    public function toArray($request)
    {
        $voucher = $this->voucher;
        $ticketType = $voucher?->categoryTicketType->ticketType;
        $categoryTicketType = $voucher?->categoryTicketType;
        $isUsed = (bool) $this->used;
        if ($voucher) {
            $finalPrice = $voucher->final_price;
        } else {
            $finalPrice = $categoryTicketType->price;
        }


        $categoryTicketTypeWithCount = CategoryTicketType::withCount('registrations')->find($categoryTicketType->id);
        $used = $categoryTicketTypeWithCount?->registrations_count ?? 0;
        return [
            'code' => $this->code,
            'is_used' => $isUsed,
            'is_valid' => !$isUsed, // valid jika belum digunakan
            'voucher' => [
                'id' => $voucher?->id,
                'name' => $voucher?->name,
                'final_price' => $voucher?->final_price,
                'is_multiple_use' => $voucher?->is_multiple_use,
                'max_usage' => $voucher?->max_usage
            ],

            'ticket_type' => $ticketType ? [
                'id' => $ticketType->id,
                'name' => $ticketType->name,
                'price' => $categoryTicketType->price,
                'quota' => $categoryTicketType->quota,
                'used' => $used,
                'remaining' => $categoryTicketType->quota - $used,
                'valid_from' => $categoryTicketType->valid_from,
                'valid_until' => $categoryTicketType->valid_until,
                'final_price' => $finalPrice,
            ] : null,
        ];
    }
}
