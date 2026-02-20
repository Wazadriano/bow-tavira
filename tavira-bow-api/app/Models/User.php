<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string|null $username
 * @property string|null $email
 * @property string|null $password
 * @property string|null $full_name
 * @property string|null $role
 * @property bool $is_active
 * @property string|null $primary_department
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property \Illuminate\Support\Carbon|null $two_factor_confirmed_at
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserDepartmentPermission> $departmentPermissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WorkItem> $workItemsResponsible
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TaskAssignment> $taskAssignments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TeamMember> $teamMemberships
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RiskThemePermission> $riskThemePermissions
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $unreadNotifications
 */
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
