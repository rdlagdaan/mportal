<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveRequestObDetail extends Model
{
    protected $table = 'hris.hr_leave_request_ob_details';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'leave_request_id',
        'time_out_school',
        'time_in_office',
        'destination',
        'driver_name',
        'vehicle_description',
        'food_allowance_amount',
        'gas_allowance_amount',
        'voucher_number',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'time_out_school' => 'datetime',
        'time_in_office' => 'datetime',
        'food_allowance_amount' => 'decimal:2',
        'gas_allowance_amount' => 'decimal:2',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(HrLeaveRequest::class, 'leave_request_id');
    }
}