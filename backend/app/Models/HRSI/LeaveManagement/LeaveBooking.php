<?php

namespace App\Models\HRSI\LeaveManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LeaveBooking extends Model
{
    protected $fillable = ['employee_id','leave_type_id'];

    /** Set the booking period as [start, end). */
    public function setPeriod($startDate, $endDate): void
    {
        DB::statement(
            "UPDATE leave_bookings SET period = daterange(?, ?, '[]') WHERE id = ?",
            [$startDate, $endDate, $this->id]
        );
    }
}
