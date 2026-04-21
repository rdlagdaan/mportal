<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveRequestRoute extends Model
{
    protected $table = 'hris.hr_leave_request_routes';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'leave_request_id',
        'step_no',
        'approver_employee_id',
        'status',
        'acted_at',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'step_no' => 'integer',
        'acted_at' => 'datetime',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(HrLeaveRequest::class, 'leave_request_id');
    }

    public function approver()
    {
        return $this->belongsTo(HrEmployee::class, 'approver_employee_id');
    }
}