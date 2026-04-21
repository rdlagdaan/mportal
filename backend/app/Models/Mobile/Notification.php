<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'mobile.notifications';
    protected $primaryKey = 'id';

    protected $fillable = [
        'title',
        'message',
        'type',
        'created_by',
        'event_id', 
        'appointment_id',
        'scheduled_at',   
    'send_status',    
    ];

    protected $casts = [
        'event_isread' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function creator()
    {
        return $this->belongsTo(Users::class, 'created_by');
    }

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class, 'notification_id');
    }

    public function targets()
    {
        return $this->hasMany(NotificationTarget::class, 'notification_id');
    }
}