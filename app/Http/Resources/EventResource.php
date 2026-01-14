<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'code_prefix'               => $this->code_prefix,
            'name'                      => $this->name,
            'start_date'                => $this->start_date,
            'end_date'                  => $this->end_date,
            'status'                    => $this->status,
            'size'                      => $this->size,
            'slug'                      => $this->slug,
            'location'                  => $this->location,
            'location_gmaps_url'        => $this->location_gmaps_url,
            'registration_start_date'   => $this->registration_start_date,
            'registration_end_date'     => $this->registration_end_date,
            'description'               => $this->description,
            'rpc_start_date'            => $this->rpc_start_date,
            'rpc_end_date'              => $this->rpc_end_date,
            'rpc_collection_times'      => $this->rpc_collection_times,
            'rpc_collection_location'   => $this->rpc_collection_location,
            'rpc_collection_gmaps_url'  => $this->rpc_collection_gmaps_url,
            'event_url'                 => $this->event_url,
            'ig_url'                    => $this->ig_url,
            'fb_url'                    => $this->fb_url,
            'contact_email'             => $this->contact_email,
            'contact_phone'             => $this->contact_phone,
            'event_owner'               => $this->event_owner,
            'event_organizer'           => $this->event_organizer,
            'event_logo'                => $this->event_logo ? asset('storage/' . $this->event_logo) : null,
            'event_banner'              => $this->event_banner ? asset('storage/' . $this->event_banner) : null,
            'jersey_size_image'         => $this->jersey_size_image ? asset('storage/' . $this->jersey_size_image) : null,
            'slides'                    => EventSlideResource::collection($this->whenLoaded('slides')),
            'categories'                => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at'                => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at'                => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
