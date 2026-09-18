<?php

namespace App\Policies;

use App\Models\ExerciseLog;
use App\Models\User;

class ExerciseLogPolicy
{
    public function view(User $user, ExerciseLog $exerciseLog): bool
    {
        return $user->id === $exerciseLog->user_id;
    }

    public function update(User $user, ExerciseLog $exerciseLog): bool
    {
        return $user->id === $exerciseLog->user_id;
    }

    public function delete(User $user, ExerciseLog $exerciseLog): bool
    {
        return $user->id === $exerciseLog->user_id;
    }
}
