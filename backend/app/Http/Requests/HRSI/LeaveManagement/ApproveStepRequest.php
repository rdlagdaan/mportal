<?php

namespace App\Http\Requests\HRSI\LeaveManagement;

use Illuminate\Foundation\Http\FormRequest;

class ApproveStepRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['remarks' => ['nullable','string','max:2000']]; }
}
