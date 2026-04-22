<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->agency_id === $task->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->agency_id === $task->agency_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->agency_id === $task->agency_id;
    }
}
