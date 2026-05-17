<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Model;

class NotificationAttachment extends Model
{
    protected $table = 'mobile.notification_attachments';

    protected $fillable = [
        'notification_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by'
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}   