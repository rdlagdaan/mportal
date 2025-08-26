<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SchoolYear extends Model
{
    protected $fillable = ['sy_code'];

    /** Set the school year period as [start, end). */
    public function setPeriod($startDate, $endDate): void
    {
        DB::statement(
            "UPDATE school_years SET period = daterange(?, ?, '[]') WHERE id = ?",
            [$startDate, $endDate, $this->id]
        );
    }
}
