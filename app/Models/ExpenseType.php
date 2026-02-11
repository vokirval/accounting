<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class);
    }

    public function editors(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'expense_type_user_permissions',
            'expense_type_id',
            'user_id',
        );
    }
}
