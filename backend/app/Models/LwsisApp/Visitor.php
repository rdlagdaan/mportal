<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Visitor extends Model
{
    use HasApiTokens;

    /**
     * PostgreSQL schema + table.
     */
    protected $table = 'mobile.visitors';

    /**
     * Mass assignable fields.
     */
    protected $fillable = [
        'uuid',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'mobile_number',
        'password',
        'birth_date',
        'address',
        'profile_photo',
        'identity_type',
        'identity_reference',
        'account_status',
        'email_verified_at',
        'mobile_verified_at',
    ];

    /**
     * Fields hidden from JSON responses.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'birth_date' => 'date:Y-m-d',
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Automatically generate UUID when creating a visitor.
     */
    protected static function booted(): void
    {
        static::creating(function (Visitor $visitor) {
            if (empty($visitor->uuid)) {
                $visitor->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Visitor visits.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(
            VisitorVisit::class,
            'visitor_id'
        );
    }
}