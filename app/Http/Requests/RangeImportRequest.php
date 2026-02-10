<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RangeImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:BODY,PCB'],
            'start_sn' => ['required', 'regex:/^[0-9]{8}$/'],
            'end_sn' => ['required', 'regex:/^[0-9]{8}$/'],
            'lot_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_sn.regex' => '8桁の数字で入力してください（例：00001234）',
            'end_sn.regex' => '8桁の数字で入力してください（例：00001234）',
        ];
    }
}
