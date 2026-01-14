<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'distance'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketTypes(): BelongsToMany
    {
        return $this->belongsToMany(TicketType::class)
            ->withPivot('id', 'price', 'quota', 'valid_from', 'valid_until')
            ->withTimestamps();
    }

    public function categoryTicketTypes(): HasMany
    {
        return $this->hasMany(CategoryTicketType::class);
    }
}
