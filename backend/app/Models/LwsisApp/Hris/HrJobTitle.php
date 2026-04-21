<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrJobTitle extends Model
{
    protected $table = 'hris.hr_job_titles';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'code',
        'title_name',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employeeAppointments()
    {
        return $this->hasMany(HrEmployeeAppointment::class, 'job_title_id');
    }
}