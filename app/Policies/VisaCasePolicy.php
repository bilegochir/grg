<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisaCase;

class VisaCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function view(User $user, VisaCase $visaCase): bool
    {
        return $user->agency_id === $visaCase->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->agency_id !== null && $user->canManageVisaCases();
    }

    public function update(User $user, VisaCase $visaCase): bool
    {
        return $user->agency_id === $visaCase->agency_id && $user->canManageVisaCases();
    }

    public function delete(User $user, VisaCase $visaCase): bool
    {
        return $user->agency_id === $visaCase->agency_id && $user->canManageVisaCases();
    }
}
