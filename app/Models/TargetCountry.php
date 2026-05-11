<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TargetCountry extends Model
{
    /** @use HasFactory<\Database\Factories\TargetCountryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function visaTypes(): HasMany
    {
        return $this->hasMany(VisaType::class)->orderBy('name');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(VisaCase::class)->latest();
    }
}
