<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'staff'], true);
    }

    public function rules(): array
    {
        return [
            'body_sn' => ['required', 'regex:/^[0-9]{8}$/'],
            'pcb_sn' => ['required', 'regex:/^[0-9]{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'body_sn.regex' => '8桁の数字で入力してください（例：00001234）',
            'pcb_sn.regex' => '8桁の数字で入力してください（例：00001234）',
        ];
    }
}
