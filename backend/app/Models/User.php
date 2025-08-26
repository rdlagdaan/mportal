<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // <-- add this
use Laravel\Sanctum\HasApiTokens;              // ✅ correct namespace
use Illuminate\Auth\Notifications\ResetPassword;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasApiTokens, Notifiable, HasRoles; // <-- and use it
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'mobile_number',
        'password',
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

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
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


    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    public function appAccesses()
    {
        return $this->hasMany(\App\Models\UserAppAccess::class);
    }

    /**
     * Check if the user has access to a specific sub-app by code.
     * Valid codes: 'LRWSIS', 'OPENU', 'MICRO'
     */
    public function hasApp(string $code): bool
    {
        return $this->appAccesses()
            ->whereHas('app', function ($q) use ($code) {
                $q->where('code', strtoupper($code));
            })
            ->where('is_enabled', true)
            ->exists();
    }




}
