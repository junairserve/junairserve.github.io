<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InspectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'inspector'], true);
    }

    public function rules(): array
    {
        return [
            'body_sn' => ['required', 'regex:/^[0-9]{8}$/'],
            'cert_no' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'place' => ['required', 'string', 'max:255'],
            'method' => ['required', 'string', 'max:255'],
            'result' => ['required', 'in:PASS,FAIL'],
        ];
    }

    public function messages(): array
    {
        return [
            'body_sn.regex' => '8桁の数字で入力してください（例：00001234）',
        ];
    }
}
