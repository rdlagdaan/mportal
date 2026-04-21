<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeTeachingCollege extends Model
{
    protected $table = 'hris.hr_employee_teaching_colleges';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'college_id',
        'start_date',
        'end_date',
        'is_primary',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function college()
    {
        return $this->belongsTo(HrCollege::class, 'college_id');
    }
}