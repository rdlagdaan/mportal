<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserIdentityLink extends Model
{
    use HasFactory;

    protected $table = 'public.user_identity_links';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'kind',
        'employee_number',
        'student_number',
        'guest_number',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'employee_number' => 'string',
        'student_number' => 'integer',
        'guest_number' => 'integer',
        'kind' => 'string', // Postgres enum
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}