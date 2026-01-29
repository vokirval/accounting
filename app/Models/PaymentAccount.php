<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'paid_account_id');
    }
}
