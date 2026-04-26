<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'agency_id',
        'name',
        'email',
        'role',
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
            'role' => UserRole::class,
            'password' => 'hashed',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function ownedClients(): HasMany
    {
        return $this->hasMany(Client::class, 'owner_id');
    }

    public function assignedVisaCases(): HasMany
    {
        return $this->hasMany(VisaCase::class, 'assigned_user_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_user_id');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'author_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by_id');
    }

    public function canManageCompanySettings(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canManageWorkflowSettings(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canManageTeam(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canManageClients(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::CaseManager], true);
    }

    public function canManageVisaCases(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::CaseManager], true);
    }

    public function canManageTasks(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::CaseManager, UserRole::Staff], true);
    }

    public function roleLabel(): string
    {
        return $this->role->label();
    }
}
