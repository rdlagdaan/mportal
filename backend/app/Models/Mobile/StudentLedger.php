<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentLedger extends Model
{
    use HasFactory;

    // Specify the table and schema
    protected $table = 'mobile.student_ledger';

    // Primary key (auto-increment)
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    // Timestamps
    public $timestamps = false; // since you have created_at/updated_at manually

    // Mass assignable attributes
    protected $fillable = [
        'student_number',
        'sem',
        'sy',
        'total_assessment',
        'tuition_fee',
        'miscellaneous_fee',
        'other_fee',
        'total_payment',
        'balance',
        'created_at',
        'updated_at',
    ];

    // Optional: cast numeric fields
    protected $casts = [
        'total_assessment' => 'float',
        'tuition_fee' => 'float',
        'miscellaneous_fee' => 'float',
        'other_fee' => 'float',
        'total_payment' => 'float',
        'balance' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // app/Models/Ledger.php
public function payments()
{
    return $this->hasMany(Payment::class, 'student_number', 'student_number')
                ->whereColumn('sy', 'ledgers.sy')  // match school year
                ->whereColumn('sem', 'ledgers.sem'); // match semester
}

}