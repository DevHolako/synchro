<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property AccountStatus $status
 * @property Carbon|null $activated_at
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TeacherProfile|null $teacherProfile
 * @property-read StudentProfile|null $studentProfile
 * @property-read InvitationToken|null $latestInvitation
 */
#[Fillable(['name', 'email', 'password', 'role', 'status', 'activated_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => AccountStatus::class,
            'activated_at' => 'datetime',
        ];
    }

    public function hasPermission(Permission|string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    /**
     * Get the permission values granted through the user's role bundle.
     *
     * @return array<int, string>
     */
    public function permissionValues(): array
    {
        return array_map(
            fn (Permission $permission): string => $permission->value,
            $this->role->permissions(),
        );
    }

    public function isActive(): bool
    {
        return $this->status === AccountStatus::Active;
    }

    /**
     * @return HasMany<Module, $this>
     */
    public function taughtModules(): HasMany
    {
        return $this->hasMany(Module::class, 'teacher_id');
    }

    /**
     * @return HasOne<TeacherProfile, $this>
     */
    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    /**
     * @return HasOne<StudentProfile, $this>
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * @return HasMany<InvitationToken, $this>
     */
    public function invitationTokens(): HasMany
    {
        return $this->hasMany(InvitationToken::class);
    }

    /**
     * @return HasOne<InvitationToken, $this>
     */
    public function latestInvitation(): HasOne
    {
        return $this->hasOne(InvitationToken::class)->latestOfMany();
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeTeachers(Builder $query): void
    {
        $query->where('role', UserRole::Teacher);
    }
}
