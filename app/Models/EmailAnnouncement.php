<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmailAnnouncement extends Model
{
    protected $fillable = [
        'event_id',
        'subject',
        'html_template',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'created_by',
        'sent_at',
        'completed_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations(): BelongsToMany
    {
        return $this->belongsToMany(Registration::class, 'email_announcement_registration')
            ->withPivot('status', 'email_log_id', 'error_message', 'sent_at')
            ->withTimestamps();
    }
}
