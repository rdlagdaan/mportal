<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeSupervisor extends Model
{
    protected $table = 'hris.hr_employee_supervisors';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'supervisor_employee_id',
        'effective_from',
        'effective_to',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // subordinate
    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    // supervisor (boss)
    public function supervisor()
    {
        return $this->belongsTo(HrEmployee::class, 'supervisor_employee_id');
    }
}