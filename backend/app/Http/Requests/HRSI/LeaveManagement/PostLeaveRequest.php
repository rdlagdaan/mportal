<?php

namespace App\Http\Requests\HRSI\LeaveManagement;

use Illuminate\Foundation\Http\FormRequest;

class PostLeaveRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['charge_to_leave_type_id' => ['nullable','integer','exists:leave_types,id']]; }
}
