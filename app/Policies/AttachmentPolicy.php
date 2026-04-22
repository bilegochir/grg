<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        return $user->agency_id === $attachment->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->agency_id === $attachment->agency_id;
    }
}
