<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequestUpdateRequest extends FormRequest
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

        if ($this->has('commission') && $this->input('commission') === '') {
            $this->merge(['commission' => null]);
        }
    }

    public function rules(): array
    {
        return [
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
            'commission' => ['nullable', 'numeric', 'min:0'],
            'purchase_reference' => ['nullable', 'string', 'max:255'],
            'ready_for_payment' => ['nullable', 'boolean'],
            'paid' => ['nullable', 'boolean'],
            'paid_account_id' => ['nullable', 'integer', 'exists:payment_accounts,id'],
            'receipt_url' => ['nullable', 'string', 'max:2048'],
            'receipt_file' => [
                'nullable',
                'file',
                'mimetypes:application/pdf,application/octet-stream,application/pkcs7-mime,application/x-pkcs7-mime',
                'max:10240',
            ],
        ];
    }
}
