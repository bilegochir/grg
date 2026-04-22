<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\VisaCaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class VisaCaseTaskTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\VisaCaseTaskTemplateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agency_id',
        'visa_case_status',
        'title',
        'description',
        'priority',
        'due_in_days',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visa_case_status' => VisaCaseStatus::class,
            'priority' => TaskPriority::class,
            'due_in_days' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
