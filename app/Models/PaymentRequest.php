<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'expense_type_id',
        'expense_category_id',
        'requisites',
        'amount',
        'purchase_reference',
        'ready_for_payment',
        'paid',
        'paid_account_id',
        'receipt_url',
    ];

    protected $casts = [
        'ready_for_payment' => 'boolean',
        'paid' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function paidAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'paid_account_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'payment_request_user');
    }

    public function history(): HasMany
    {
        return $this->hasMany(PaymentRequestHistory::class);
    }
}
