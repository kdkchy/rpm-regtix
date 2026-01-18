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

    protected static function booted()
    {
        static::updating(function (EventSlide $slide) {
            // Jika image_path null atau empty saat update, preserve existing value
            if (empty($slide->image_path) && $slide->exists) {
                $slide->image_path = $slide->getOriginal('image_path');
            }
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
