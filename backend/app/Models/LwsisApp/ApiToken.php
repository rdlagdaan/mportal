<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| API TOKENS
|--------------------------------------------------------------------------
*/
class ApiToken extends Model
{
    use HasFactory;

    protected $table = 'iam.api_tokens';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'system_id',
        'token_hash',
        'expires_at',
        'token_sha256'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
