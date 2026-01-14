<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    //
    protected $fillable = [
        'title',
        'event_id',
        'subject',
        'html_template'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function registrations()
    {
        return $this->belongsToMany(Registration::class, 'campaign_registration')
            ->withPivot('status')
            ->withTimestamps();
    }
}
