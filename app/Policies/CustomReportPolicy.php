<?php

namespace App\Policies;

use App\Models\CustomReport;
use App\Models\User;

class CustomReportPolicy
{
    public function view(User $user, CustomReport $report): bool
    {
        return $user->id === $report->user_id;
    }

    public function update(User $user, CustomReport $report): bool
    {
        return $user->id === $report->user_id;
    }

    public function delete(User $user, CustomReport $report): bool
    {
        return $user->id === $report->user_id;
    }
}
