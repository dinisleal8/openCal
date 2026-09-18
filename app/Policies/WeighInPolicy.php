<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeighIn;

class WeighInPolicy
{
    public function view(User $user, WeighIn $weighIn): bool
    {
        return $user->id === $weighIn->user_id;
    }

    public function update(User $user, WeighIn $weighIn): bool
    {
        return $user->id === $weighIn->user_id;
    }

    public function delete(User $user, WeighIn $weighIn): bool
    {
        return $user->id === $weighIn->user_id;
    }
}
