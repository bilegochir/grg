<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Agency extends Model
{
    /** @use HasFactory<\Database\Factories\AgencyFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'website',
        'address',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $agency): void {
            if (blank($agency->slug)) {
                $agency->slug = self::generateUniqueSlug($agency->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function visaCases(): HasMany
    {
        return $this->hasMany(VisaCase::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function visaCaseTaskTemplates(): HasMany
    {
        return $this->hasMany(VisaCaseTaskTemplate::class);
    }

    public function visaCaseStatusTemplates(): HasMany
    {
        return $this->hasMany(VisaCaseStatusTemplate::class);
    }

    public function taskStatusTemplates(): HasMany
    {
        return $this->hasMany(TaskStatusTemplate::class);
    }

    public static function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'agency';
        $candidate = $baseSlug;
        $suffix = 2;

        while (self::query()->where('slug', $candidate)->exists()) {
            $candidate = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
