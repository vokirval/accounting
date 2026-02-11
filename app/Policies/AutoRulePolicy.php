<?php

namespace App\Policies;

use App\Models\AutoRule;
use App\Models\User;

class AutoRulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AutoRule $autoRule): bool
    {
        return $user->isAdmin() || $autoRule->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AutoRule $autoRule): bool
    {
        return $user->isAdmin() || $autoRule->user_id === $user->id;
    }

    public function delete(User $user, AutoRule $autoRule): bool
    {
        return $user->isAdmin() || $autoRule->user_id === $user->id;
    }
}
