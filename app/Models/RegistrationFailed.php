<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationFailed extends Model
{
    use HasFactory;

    protected $table = 'registration_failed';

    protected $fillable = [
        'original_id',
        'category_ticket_type_id',
        'voucher_code_id',
        'full_name',
        'email',
        'phone',
        'gender',
        'place_of_birth',
        'dob',
        'address',
        'district',
        'province',
        'country',
        'id_card_type',
        'id_card_number',
        'emergency_contact_name',
        'emergency_contact_phone',
        'blood_type',
        'nationality',
        'jersey_size',
        'community_name',
        'bib_name',
        'reg_id',
        'is_validated',
        'validated_by',
        'status',
        'transaction_code',
        'payment_status',
        'payment_type',
        'payment_method',
        'gross_amount',
        'paid_at',
        'payment_token',
        'payment_url',
        'qr_code_path',
        'registration_code',
        'registration_date',
        'invitation_code',
        'last_printed_at',
        'failed_at',
        'failed_reason',
        'restored_at',
        'restored_by',
        'restore_note',
    ];

    protected $casts = [
        'dob' => 'date',
        'registration_date' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'restored_at' => 'datetime',
        'is_validated' => 'boolean',
        'gross_amount' => 'decimal:2',
    ];

    /**
     * Get the category ticket type that owns the registration.
     */
    public function categoryTicketType()
    {
        return $this->belongsTo(CategoryTicketType::class, 'category_ticket_type_id');
    }

    /**
     * Get the voucher code that owns the registration.
     */
    public function voucherCode()
    {
        return $this->belongsTo(VoucherCode::class, 'voucher_code_id');
    }

    /**
     * Get the user who restored this registration.
     */
    public function restoredBy()
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    /**
     * Check if this registration has been restored.
     */
    public function isRestored(): bool
    {
        return !is_null($this->restored_at);
    }
}
