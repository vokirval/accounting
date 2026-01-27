<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_type_id' => ['required', 'integer', 'exists:expense_types,id'],
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'requisites' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'purchase_reference' => ['nullable', 'string', 'max:255'],
            'ready_for_payment' => ['nullable', 'boolean'],
            'paid' => ['nullable', 'boolean'],
            'paid_account_id' => ['nullable', 'integer', 'exists:payment_accounts,id'],
            'receipt_url' => ['nullable', 'string', 'max:2048'],
            'receipt_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
