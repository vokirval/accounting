<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequestHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'payment_request_id',
        'user_id',
        'action',
        'changed_fields',
        'created_at',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'created_at' => 'datetime',
    ];

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
