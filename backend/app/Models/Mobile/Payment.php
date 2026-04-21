<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // Specify the table name (optional if it matches Laravel's naming convention)
    protected $table = 'mobile.payments';

    // Primary key (optional if it's 'id')
    protected $primaryKey = 'id';

    // Allow mass assignment for these fields
    protected $fillable = [
        'or_number',
        'sem',
        'sy',
        'student_number',
        'amount',
        'payment_for',
        'payment_date',
        'created_at',
        'updated_at',
    ];

    // Cast fields to specific types
    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Disable timestamps if you are manually managing them
    public $timestamps = true;

    // Optional: relation to student ledger if needed
    public function ledger()
    {
        return $this->belongsTo(Ledger::class, 'student_number', 'student_number');
    }
}