<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'code',
        'used',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function registration()
    {
        // SINGLE USE
        return $this->hasOne(Registration::class, 'voucher_code_id');
    }
    public function registrations()
    {
        // MULTI USE
        return $this->hasMany(Registration::class, 'voucher_code_id');
    }

    /**
     * Get confirmed/paid registrations only
     * Used for counting actual voucher usage
     */
    public function confirmedRegistrations()
    {
        return $this->registrations()
            ->where(function($query) {
                $query->whereIn('status', ['confirmed', 'paid'])
                      ->orWhere('payment_status', 'paid');
            });
    }
}
