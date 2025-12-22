<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'registration_id',
        'brevo_message_id',
        'email',
        'status',
        'status_details',
        'event_type',
        'sent_at',
        'delivered_at',
        'bounced_at',
        'error_message',
    ];

    protected $casts = [
        'status_details' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    /**
     * Get the registration that owns the email log.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Get the latest email log for a registration.
     */
    public static function getLatestForRegistration(int $registrationId): ?self
    {
        return self::where('registration_id', $registrationId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Update status based on event type from webhook.
     */
    public function updateStatusFromEvent(string $eventType, array $details = []): void
    {
        $statusMap = [
            'delivered' => 'delivered',
            'bounced' => 'bounced',
            'invalid' => 'invalid',
            'hardBounce' => 'hardBounce',
            'softBounce' => 'softBounce',
        ];

        $status = $statusMap[$eventType] ?? null;
        
        if ($status) {
            $updateData = [
                'status' => $status,
                'event_type' => $eventType,
            ];

            // Merge status_details instead of replacing
            $currentDetails = $this->status_details ?? [];
            $updateData['status_details'] = array_merge($currentDetails, $details);

            // Update timestamps based on event type
            if ($eventType === 'delivered' && !$this->delivered_at) {
                $updateData['delivered_at'] = now();
            }
            
            if (in_array($eventType, ['bounced', 'hardBounce', 'softBounce']) && !$this->bounced_at) {
                $updateData['bounced_at'] = now();
            }

            $this->update($updateData);
        }
    }
}
