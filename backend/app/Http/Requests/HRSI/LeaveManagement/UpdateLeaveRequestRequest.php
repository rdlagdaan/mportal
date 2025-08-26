<?php

namespace App\Http\Requests\HRSI\LeaveManagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'leave_type_id'           => ['sometimes','integer','exists:leave_types,id'],
            'charge_to_leave_type_id' => ['nullable','integer','exists:leave_types,id'],
            'start_date'              => ['sometimes','date'],
            'end_date'                => ['sometimes','date','after:start_date'],
            'part_day'                => ['nullable','in:AM,PM'],
            'reason_text'             => ['nullable','string','max:2000'],
        ];
    }
}
