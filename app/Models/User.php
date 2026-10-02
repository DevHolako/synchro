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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

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
 * @property string|null $calendar_feed_token
 * @property string|null $calendar_feed_token_hash
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TeacherProfile|null $teacherProfile
 * @property-read StudentProfile|null $studentProfile
 * @property-read InvitationToken|null $latestInvitation
 * @property-read Collection<int, TeacherUnavailability> $unavailabilities
 */
#[Fillable(['name', 'email', 'password', 'role', 'status', 'activated_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'calendar_feed_token', 'calendar_feed_token_hash'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
            'calendar_feed_token' => 'encrypted',
        ];
    }

    public function hasPermission(Permission|string $permission): bool
    {
        return $this->role->hasPermission($permission);
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
     * @return HasMany<TeacherUnavailability, $this>
     */
    public function unavailabilities(): HasMany
    {
        return $this->hasMany(TeacherUnavailability::class, 'teacher_id');
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
     * Whether the account anchors the timetable or its audit trail: it taught a course
     * session, overrode a scheduling conflict, appears on an attendance register, or published,
     * sat, invigilated or rescheduled an exam.
     * Such accounts must not be deleted.
     */
    public function hasSchedulingHistory(): bool
    {
        return CourseSession::query()->where('teacher_id', $this->id)->exists()
            || ConflictOverride::query()->where('user_id', $this->id)->exists()
            || SessionAttendance::query()->where('student_id', $this->id)->orWhere('recorded_by', $this->id)->exists()
            || Exam::query()->where('published_by', $this->id)->exists()
            || ExamCandidate::query()->where('student_id', $this->id)->orWhere('checked_in_by', $this->id)->exists()
            || ExamInvigilator::query()->where('teacher_id', $this->id)->exists()
            || ExamReschedule::query()->where('user_id', $this->id)->exists();
    }

    /**
     * The name on official exam documents and at the exam door: SURNAME Given name from a
     * student's official fields. The display name, which the student may edit, stands in only
     * when no surname was recorded (and for everyone who is not a student).
     */
    public function officialName(): string
    {
        $profile = $this->studentProfile;

        if ($profile === null || blank($profile->last_name)) {
            return $this->name;
        }

        return trim(mb_strtoupper(trim((string) $profile->last_name)).' '.trim((string) $profile->first_name));
    }

    /**
     * The student's marks on attendance registers.
     *
     * @return HasMany<SessionAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class, 'student_id');
    }

    /**
     * The students on a session's attendance register: those in its groups, plus anyone
     * already marked on it who has since changed group.
     *
     * @param  Builder<User>  $query
     */
    public function scopeOnRegisterOf(Builder $query, CourseSession $session): void
    {
        $groupIds = DB::table('course_session_student_group')
            ->where('course_session_id', $session->id)
            ->select('student_group_id');

        $query->where(fn (Builder $query) => $query
            ->whereHas('studentProfile', fn (Builder $profiles) => $profiles->whereIn('student_group_id', $groupIds))
            ->orWhereHas('attendances', fn (Builder $marks) => $marks->where('course_session_id', $session->id)));
    }

    /**
     * Feed tokens are looked up by this hash; the token itself is only kept, encrypted, for display.
     */
    public static function hashCalendarFeedToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeTeachers(Builder $query): void
    {
        $query->where('role', UserRole::Teacher);
    }
}
