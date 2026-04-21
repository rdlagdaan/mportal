<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LwsisApp\User;
use App\Models\Mobile\Notification;

class IamUserNotification extends Model
{
    use HasFactory;

    protected $table = 'mobile.iam_user_notifications';
    protected $primaryKey = 'id';
    public $timestamps = true; 

    protected $fillable = [
        'user_id',
        'notification_id',
        'is_read',
        'delivered_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // ✅ IAM USER
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ✅ NOTIFICATION
    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }
}