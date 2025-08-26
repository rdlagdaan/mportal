<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Holiday extends Model
{
    protected $fillable = ['school_year_id','name','created_by','is_working_day'];

    public function schoolYear(){ return $this->belongsTo(SchoolYear::class); }

    /** Set the holiday period as [start, end). */
    public function setPeriod($startDate, $endDate): void
    {
        DB::statement(
            "UPDATE holidays SET period = daterange(?, ?, '[]') WHERE id = ?",
            [$startDate, $endDate, $this->id]
        );
    }
}
