<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaCaseGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'notes',
        'created_by_user_id',
    ];

    public function cases(): HasMany
    {
        return $this->hasMany(VisaCase::class)->orderByDesc('is_group_primary')->orderBy('created_at');
    }

    public function primaryCase(): HasMany
    {
        return $this->hasMany(VisaCase::class)->where('is_group_primary', true);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
