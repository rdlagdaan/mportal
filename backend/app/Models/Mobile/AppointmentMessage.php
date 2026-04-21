<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentMessage extends Model
{
    use HasFactory;

    protected $table = 'mobile.appointment_messages';
    protected $primaryKey = 'id';
    public $timestamps = false; // 🚨 Important: You only have created_at column, not updated_at

    protected $fillable = [
        'appointment_id',
        'sender_user_id',
        'message',
        'created_at',
        'sender_id',
    ];

    protected $casts = [
        'appointment_id' => 'integer',
        'sender_user_id' => 'integer',
        'message' => 'string',
        'created_at' => 'datetime',
    ];

    public function sender()
{
    return $this->belongsTo(\App\Models\Mobile\Users::class, 'sender_user_id');
}

}