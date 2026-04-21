<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeChangeRequest extends Model
{
    protected $table = 'hris.hr_employee_change_requests';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'batch_id',
        'employee_id',
        'section',
        'action',
        'target_table',
        'target_row_id',
        'proposed_data',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'proposed_data' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function batch()
    {
        return $this->belongsTo(HrEmployeeChangeBatch::class, 'batch_id');
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}