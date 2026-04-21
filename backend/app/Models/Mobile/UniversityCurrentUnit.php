<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniversityCurrentUnit extends Model
{
    use HasFactory;

    protected $table = 'mobile.university_current_unit';

    protected $fillable = [
        'employee_id',
        'office_id',
        'employee_type_id',
        'main_unit_flag',
    ];

    protected $casts = [
        'main_unit_flag' => 'boolean',
    ];

    /**
     * A current unit belongs to one office.
     */
    public function office()
    {
        return $this->belongsTo(UniversityOffice::class, 'office_id', 'id');
    }
}
