<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationAttachment extends Model
{
    use HasFactory;

    protected $table = 'mobile.notification_attachments';

    protected $fillable = [
        'notification_id',
        'file_path',
        'file_type',
    ];

    protected $casts = [
        'notification_id' => 'integer',
        'file_path'       => 'string',
        'file_type'       => 'string',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    // 🔗 Relationship
    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }
}