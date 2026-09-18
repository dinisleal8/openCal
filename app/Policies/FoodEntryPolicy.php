<?php

namespace App\Policies;

use App\Models\FoodEntry;
use App\Models\User;

class FoodEntryPolicy
{
    public function view(User $user, FoodEntry $foodEntry): bool
    {
        return $user->id === $foodEntry->user_id;
    }

    public function update(User $user, FoodEntry $foodEntry): bool
    {
        return $user->id === $foodEntry->user_id;
    }

    public function delete(User $user, FoodEntry $foodEntry): bool
    {
        return $user->id === $foodEntry->user_id;
    }
}
