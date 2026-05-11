<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'branch_id',
        'job_title',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'notifications_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function assignedCases(): HasMany
    {
        return $this->hasMany(VisaCase::class, 'assigned_to_user_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(VisaCaseAppointment::class, 'assigned_to_user_id');
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to_user_id');
    }

    public function getRoleNamesAttribute(): array
    {
        return $this->roles->pluck('name')->all();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function hasPermissionTo(string $permission): bool
    {
        if (! Permission::query()->exists()) {
            return in_array($permission, [
                'leads.view',
                'leads.create',
                'leads.update',
                'leads.convert',
                'applicants.view',
                'applicants.update',
                'cases.view',
                'cases.create',
                'cases.update',
                'cases.assign',
                'communications.manage',
            ], true);
        }

        return $this->allPermissions()->contains('name', $permission);
    }

    public function allPermissions(): Collection
    {
        $direct = $this->permissions;
        $viaRoles = $this->roles->loadMissing('permissions')->flatMap->permissions;

        return $direct->concat($viaRoles)->unique('id')->values();
    }
}
