<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $events = Event::with(['categories', 'categories.ticketTypes', 'categories.categoryTicketTypes', 'slides'])
        ->orderByRaw('FIELD(status, "OPEN", "TBA", "TC", "CLOSED")')
        ->orderByRaw("
            CASE 
                WHEN size = 'Large' THEN 1
                WHEN size = 'Medium' THEN 2
                WHEN size = 'Small' THEN 3
                ELSE 4
            END
        ")
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

        return EventResource::collection($events);
    }

    public function show($id) {
        if (is_numeric($id)) {
            $event = Event::with(['categories', 'categories.ticketTypes', 'slides'])->findOrFail($id);
        } else {
            $event = Event::with(['categories', 'categories.ticketTypes', 'slides'])->where('slug', $id)->firstOrFail();
        }

        return new EventResource($event);
    }
}
