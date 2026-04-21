<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTarget extends Model
{
    use HasFactory;

    protected $table = 'mobile.notification_targets';
    protected $primaryKey = 'id';

    public $timestamps = false; // ✅ your table only has created_at

    protected $fillable = [
        'notification_id',
        'target_type',
        'target_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'target_id' => 'integer',
        'target_type' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }
}