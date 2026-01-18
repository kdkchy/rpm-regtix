<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    private static ?int $systemUserId = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'event_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function validatedRegistrations()
    {
        return $this->hasMany(Registration::class, 'validated_by');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class);
    }

    public static function systemUserId(): int
    {
        if (self::$systemUserId) {
            return self::$systemUserId;
        }

        $user = self::firstOrCreate(
            ['email' => 'system@regtix.id'],
            [
                'name' => 'System_Default',
                'password' => Hash::make(Str::random(32)),
            ]
        );

        self::$systemUserId = $user->id;

        return self::$systemUserId;
    }
}
