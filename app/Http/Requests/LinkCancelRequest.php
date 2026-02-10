<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkCancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'staff'], true);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
