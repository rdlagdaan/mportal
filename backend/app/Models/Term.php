<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Term extends Model
{
    protected $fillable = ['school_year_id','semester_id','code','name','active'];

    protected $casts = ['active' => 'bool'];

    public function schoolYear(){ return $this->belongsTo(SchoolYear::class); }
    public function semester()  { return $this->belongsTo(Semester::class); }

    /** Set the term period as [start, end). */
    public function setPeriod($startDate, $endDate): void
    {
        DB::statement(
            "UPDATE terms SET period = daterange(?, ?, '[]') WHERE id = ?",
            [$startDate, $endDate, $this->id]
        );
    }
}
