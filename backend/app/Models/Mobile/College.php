<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class College extends Model
{
    use HasFactory;

    // ✅ Table name (with schema)
    protected $table = 'mobile.colleges';

    // ✅ Primary key
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    // ✅ Allow mass assignment
    protected $fillable = [
        'name',
        'college_code',
    ];

    // ✅ Enable timestamps
    public $timestamps = true;

    // ✅ Default timestamp columns
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // ✅ Cast attributes for cleaner data handling
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'college_code' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ Relationship (one college → many enrollment histories)
    public function enrollmentHistories()
    {
        return $this->hasMany(EnrollmentHistory::class, 'college_code', 'college_code');
    }
}