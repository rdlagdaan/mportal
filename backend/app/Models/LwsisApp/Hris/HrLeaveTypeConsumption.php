<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveTypeConsumption extends Model
{
    protected $table = 'hris.hr_leave_type_consumption';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'leave_type_id',
        'mode',
        'consume_from_leave_type_id',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function leaveType()
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }

    public function consumeFrom()
    {
        return $this->belongsTo(HrLeaveType::class, 'consume_from_leave_type_id');
    }
}