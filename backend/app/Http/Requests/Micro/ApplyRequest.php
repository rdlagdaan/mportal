<?php

namespace App\Http\Requests\Micro;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ApplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'last_name'        => $this->input('last_name', $this->input('lastName')),
            'first_name'       => $this->input('first_name', $this->input('firstName')),
            'middle_name'      => $this->input('middle_name', $this->input('middleName')),
            'mobile'           => $this->input('mobile', $this->input('mobile_number', $this->input('mobileNumber'))),
            'email'            => $this->input('email'),
            'password'         => $this->input('password'),
            'confirm_password' => $this->input('confirm_password', $this->input('confirmPassword')),
            'consent'          => $this->boolean('consent'),
        ]);
    }

    public function rules(): array
    {
        return [
            'last_name'        => ['required','string','max:120'],
            'first_name'       => ['required','string','max:120'],
            'middle_name'      => ['nullable','string','max:120'],
            'mobile'           => ['required','string','max:30'],
            'email'            => ['required','email','max:190','unique:users,email'],
            'password'         => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'confirm_password' => ['required','same:password'],
            'consent'          => ['accepted'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();
        $data += [
            'lastName'        => $data['last_name']        ?? null,
            'firstName'       => $data['first_name']       ?? null,
            'middleName'      => $data['middle_name']      ?? null,
            'confirmPassword' => $data['confirm_password'] ?? null,
        ];
        return data_get($data, $key, $default);
    }
}
