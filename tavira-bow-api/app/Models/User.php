<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'full_name',
        'role',
        'is_active',
        'primary_department',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // ========== Relationships ==========

    public function departmentPermissions(): HasMany
    {
        return $this->hasMany(UserDepartmentPermission::class);
    }

    public function workItemsResponsible(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'responsible_party_id');
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function riskThemePermissions(): HasMany
    {
        return $this->hasMany(RiskThemePermission::class);
    }

    // ========== Helpers ==========

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canViewDepartment(string $department): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->departmentPermissions()
            ->where('department', $department)
            ->where('can_view', true)
            ->exists();
    }

    public function canEditInDepartment(string $department): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->departmentPermissions()
            ->where('department', $department)
            ->where('can_edit_all', true)
            ->exists();
    }

    public function canCreateInDepartment(string $department): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->departmentPermissions()
            ->where('department', $department)
            ->where('can_create_tasks', true)
            ->exists();
    }

    public function canEditStatus(string $department): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->departmentPermissions()
            ->where('department', $department)
            ->where('can_edit_status', true)
            ->exists();
    }
}
