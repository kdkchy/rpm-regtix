<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSlide extends Model
{
    protected $table = 'event_slides';

    protected $fillable = [
        'event_id',
        'image_path',
        'caption',
        'order',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
