<?php

namespace App\Http\Resources\Rpc;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    protected $token;

    public function __construct($resource, $token = null)
    {
        parent::__construct($resource);
        $this->token = $token;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get authorized events for this user
        $authorizedEvents = $this->events()->get();
        
        // Format events array dengan id dan name
        $eventsArray = $authorizedEvents->map(function ($event) {
            return [
                'id' => $event->id,
                'name' => $event->name,
            ];
        })->toArray();
        
        // Get first event ID for backward compatibility
        $firstEventId = $authorizedEvents->isNotEmpty() ? $authorizedEvents->first()->id : null;
        
        return [
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $this->token,
                'user' => [
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email,
                    'event_id' => $firstEventId, // Backward compatibility: event pertama dari authorized events
                    'events' => $eventsArray, // Array semua event yang diotorisasi
                ],
            ],
        ];
    }
}
