<?php

namespace App\Http\Resources\Rpc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintPayloadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Payload cetak berhasil diambil.',
            'data' => [
                'print_type' => $this->print_type,
                'participants' => $this->participants,
                'representative' => $this->representative,
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'operator_id' => $this->operator_id,
                    'operator_name' => $this->operator_name,
                    'event_id' => $this->event_id,
                    'event_name' => $this->event_name,
                ],
            ],
        ];
    }
}

