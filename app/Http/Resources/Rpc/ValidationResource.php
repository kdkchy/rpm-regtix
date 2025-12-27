<?php

namespace App\Http\Resources\Rpc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValidationResource extends JsonResource
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
            'message' => 'Validasi berhasil.',
            'data' => [
                'participant_id' => $this->id,
                'status' => 'VALIDATED',
                'validated_at' => $this->updated_at->toIso8601String(),
                'validated_by' => [
                    'id' => $this->validator->id ?? null,
                    'name' => $this->validator->name ?? null,
                ],
            ],
        ];
    }
}

