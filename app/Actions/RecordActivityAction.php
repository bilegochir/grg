<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordActivityAction
{
    public function execute(
        Model $subject,
        string $event,
        string $description,
        ?User $causer = null,
        array $properties = [],
    ): void {
        $subject->activities()->create([
            'event' => $event,
            'description' => $description,
            'properties' => $properties ?: null,
            'caused_by_user_id' => $causer?->id,
        ]);
    }
}
