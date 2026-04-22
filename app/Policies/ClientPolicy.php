<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function view(User $user, Client $client): bool
    {
        return $user->agency_id === $client->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function update(User $user, Client $client): bool
    {
        return $user->agency_id === $client->agency_id;
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->agency_id === $client->agency_id;
    }
}
