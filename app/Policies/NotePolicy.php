<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function view(User $user, Note $note): bool
    {
        return $user->agency_id === $note->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function delete(User $user, Note $note): bool
    {
        return $user->agency_id === $note->agency_id;
    }
}
