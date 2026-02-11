<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', Password::defaults()],
            'role' => ['required', 'string', 'in:user,accountant,admin'],
            'editable_expense_type_ids' => ['nullable', 'array'],
            'editable_expense_type_ids.*' => ['integer', 'exists:expense_types,id'],
        ];
    }
}
