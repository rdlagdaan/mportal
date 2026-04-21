<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;

    protected $table = 'mobile.user_roles';

    protected $primaryKey = 'id';

    public $timestamps = false; // no created_at / updated_at

    protected $fillable = [
        'user_id',
        'role_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'role_id' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}