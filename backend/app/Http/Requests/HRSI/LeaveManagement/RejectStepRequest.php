<?php

namespace App\Http\Requests\HRSI\LeaveManagement;

use Illuminate\Foundation\Http\FormRequest;

class RejectStepRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['remarks' => ['required','string','max:2000']]; }
}
