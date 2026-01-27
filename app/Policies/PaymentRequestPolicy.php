<?php

namespace App\Policies;

use App\Models\PaymentRequest;
use App\Models\User;

class PaymentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PaymentRequest $paymentRequest): bool
    {
        if ($user->isAdmin() || $user->isAccountant()) {
            return true;
        }

        return $paymentRequest->participants()
            ->whereKey($user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAccountant() || $user->isUser();
    }

    public function update(User $user, PaymentRequest $paymentRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($paymentRequest->paid) {
            return false;
        }

        if ($user->isAccountant()) {
            return true;
        }

        if (! $paymentRequest->ready_for_payment) {
            return $paymentRequest->participants()
                ->whereKey($user->id)
                ->exists();
        }

        return false;
    }

    public function changeStatus(User $user, PaymentRequest $paymentRequest): bool
    {
        return $this->update($user, $paymentRequest);
    }
}
