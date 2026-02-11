<?php

namespace App\Policies;

use App\Models\ExpenseType;
use App\Models\User;

class ExpenseTypePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->editableExpenseTypes()->exists();
    }

    public function view(User $user, ExpenseType $expenseType): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->editableExpenseTypes()->whereKey($expenseType->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ExpenseType $expenseType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ExpenseType $expenseType): bool
    {
        return $user->isAdmin();
    }

    public function manageCategories(User $user, ExpenseType $expenseType): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->editableExpenseTypes()->whereKey($expenseType->id)->exists();
    }
}
