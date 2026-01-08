<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    const GENDER = [
        'Male' => 'Male',
        'Female' => 'Female'
    ];

    const ID_CARD_TYPE = [
        'KTP' => 'KTP',
        'SIM' => 'SIM',
        'Kartu Pelajar' => 'Kartu Pelajar',
        'Passport' => 'Passport',
        'KITAS' => 'KITAS',
        'KITAP' => 'KITAP',
        'Other' => 'Other'
    ];

    const JERSEY_SIZES = [
        'S' => 'S',
        'M' => 'M',
        'L' => 'L',
        'XL' => 'XL',
        'XXL' => 'XXL',
    ];

    const BLOOD_TYPE = [
        'A' => 'A',
        'B' => 'B',
        'AB' => 'AB',
        'O' => 'O',
    ];
    //
    protected $fillable = [
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
        'registration_code',
        'is_validated',
        'validated_by',
        'registration_date',
        'invitation_code',
        'status',
        'transaction_code',
        'payment_status',
        'payment_type',
        'payment_method',
        'gross_amount',
        'paid_at',
        'payment_token',
        'payment_url',
        'qr_code_path'
    ];

    public function categoryTicketType()
    {
        return $this->belongsTo(CategoryTicketType::class, 'category_ticket_type_id');
    }

    public function getEventAttribute()
    {
        return $this->categoryTicketType?->category?->event;
    }

    public function getEventNameAttribute()
    {
        return $this->categoryTicketType?->category?->event?->name;
    }

    public function ticketType()
    {
        return $this->hasOneThrough(TicketType::class, CategoryTicketType::class, 'id', 'id', 'category_ticket_type_id', 'ticket_type_id');
    }
    public function event()
    {
        return $this->hasOneThrough(Event::class, TicketType::class, 'id', 'id', 'ticket_type_id', 'event_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_registration')->withPivot('status')->withTimestamps();
    }

    public function voucherCode()
    {
        return $this->belongsTo(VoucherCode::class, 'voucher_code_id');
    }
}
