<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entity_table','entity_id','action',
        'actor_user_id','actor_emp_id','changed_at','diff'
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'diff'       => 'array',
    ];
}
