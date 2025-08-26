<?php
namespace App\Models\HRSI;

use Illuminate\Database\Eloquent\Model;

class EmployeeAppointment extends Model
{
    protected $table = 'employee_appointments';
    public $timestamps = false;
    protected $fillable = [
        'employee_id','org_unit_id','position_id','rank_id','job_status_id',
        'employment_type','title_override','fte','is_primary','valid_during'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'fte' => 'float',
    ];

    public function orgUnit(){ return $this->belongsTo(OrgUnit::class,'org_unit_id'); }
    public function position(){ return $this->belongsTo(JobPosition::class,'position_id'); }
    public function rank(){ return $this->belongsTo(Rank::class,'rank_id'); }
    public function jobStatus(){ return $this->belongsTo(JobStatus::class,'job_status_id'); }

    // Helper attribute mirrors SQL in view
    public function getPositionNameAttribute() {
        return $this->title_override ?: ($this->position?->name);
    }
}
