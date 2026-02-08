<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AutoRuleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('requisites') && $this->input('requisites') === '') {
            $this->merge(['requisites' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'expense_type_id' => ['required', 'integer', 'exists:expense_types,id'],
            'expense_category_id' => [
                'required',
                'integer',
                Rule::exists('expense_categories', 'id')->where(
                    'expense_type_id',
                    $this->input('expense_type_id'),
                ),
            ],
            'requisites' => ['nullable', 'string'],
            'requisites_file' => [
                'nullable',
                'file',
                'mimetypes:application/pdf,application/octet-stream,application/pkcs7-mime,application/x-pkcs7-mime,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,image/jpeg,image/png,image/webp',
                'max:10240',
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'ready_for_payment' => ['nullable', 'boolean'],
            'frequency' => ['required', 'string', Rule::in(['once', 'daily', 'weekly', 'monthly', 'every_n_days'])],
            'interval_days' => ['nullable', 'integer', 'min:1', 'required_if:frequency,every_n_days'],
            'days_of_week' => ['nullable', 'array', 'required_if:frequency,weekly', 'min:1'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'day_of_month' => ['nullable', 'integer', 'between:1,31'],
            'start_date' => ['required', 'date'],
            'run_at' => ['required', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
