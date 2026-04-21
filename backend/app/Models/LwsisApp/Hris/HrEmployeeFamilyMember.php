<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeFamilyMember extends Model
{
    protected $table = 'hris.hr_employee_family_members';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'relationship_type_id',
        'full_name',
        'birth_date',
        'occupation',
        'employer',
        'is_dependent',
        'contact_no',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_dependent' => 'boolean',
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

    public function relationshipType()
    {
        return $this->belongsTo(HrRelationshipType::class, 'relationship_type_id');
    }
}