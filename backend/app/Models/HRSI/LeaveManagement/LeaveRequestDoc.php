<?php

namespace App\Models\HRSI\LeaveManagement;

use App\Models\HRSI\Employee;
use Illuminate\Database\Eloquent\Model;

class LeaveRequestDoc extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'leave_request_id','doc_type','file_path','uploaded_by','uploaded_at'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function request() { return $this->belongsTo(LeaveRequest::class,'leave_request_id'); }
    public function uploader(){ return $this->belongsTo(Employee::class, 'uploaded_by'); }
}
