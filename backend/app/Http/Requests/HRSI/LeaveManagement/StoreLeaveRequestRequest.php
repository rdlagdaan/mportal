<?php

namespace App\Http\Requests\HRSI\LeaveManagement;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id'             => ['required','integer','exists:employees,id'],
            'school_year_id'          => ['required','integer','exists:school_years,id'],
            'leave_type_id'           => ['required','integer','exists:leave_types,id'],
            'charge_to_leave_type_id' => ['nullable','integer','exists:leave_types,id'],
            'start_date'              => ['required','date'],
            'end_date'                => ['required','date','after:start_date'],
            'part_day'                => ['nullable','in:AM,PM'],
            'reason_text'             => ['nullable','string','max:2000'],
        ];
    }
}
