<?php

namespace App\Actions\Dashboard;

use App\Enums\ExamState;
use App\Enums\Permission;
use App\Enums\TimetablePerspective;
use App\Enums\UnavailabilityStatus;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamGrade;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\TeacherUnavailability;
use App\Models\User;
use App\Services\Scheduling\TimetableScope;
use Carbon\Carbon;

class GetDashboardDataAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();

        $canViewSchedules = $user->hasPermission(Permission::ViewSchedules);
        $canManageSchedules = $user->hasPermission(Permission::ManageSchedules);
        $canBrowseSchedules = $user->hasPermission(Permission::BrowseSchedules);
        $canViewExams = $user->hasPermission(Permission::ViewExams);
        $canManageExams = $user->hasPermission(Permission::ManageExams);
        $canReviewUnavailability = $user->hasPermission(Permission::ReviewUnavailability);
        $canDeclareUnavailability = $user->hasPermission(Permission::DeclareUnavailability);
        $canManageReferentials = $user->hasPermission(Permission::ManageReferentials);
        $canEnterGrades = $user->hasPermission(Permission::EnterGrades);
        $canViewOwnGrades = $user->hasPermission(Permission::ViewOwnGrades);

        // 1. Stats calculation based on atomic permissions
        $stats = [
            'unread_notifications' => $user->unreadNotifications()->count(),
        ];

        if ($canManageReferentials) {
            $stats['active_rooms'] = Room::query()->where('is_active', true)->count();
            $stats['active_groups'] = StudentGroup::query()->where('is_active', true)->count();
        }

        if ($canReviewUnavailability) {
            $stats['pending_unavailabilities'] = TeacherUnavailability::query()
                ->where('status', UnavailabilityStatus::Pending)
                ->count();
        } elseif ($canDeclareUnavailability) {
            $stats['my_unavailabilities'] = TeacherUnavailability::query()
                ->where('teacher_id', $user->id)
                ->where('status', UnavailabilityStatus::Pending)
                ->count();
        }

        if ($canManageSchedules) {
            $stats['sessions_this_week'] = CourseSession::query()
                ->whereBetween('starts_at', [$startOfWeek, $endOfWeek])
                ->count();
        } elseif ($canViewSchedules) {
            $scope = TimetableScope::mine($user);
            $query = CourseSession::query()->whereBetween('starts_at', [$startOfWeek, $endOfWeek]);

            if ($scope->perspective === TimetablePerspective::Group && $scope->subjectId !== null) {
                $query->whereHas('studentGroups', fn ($g) => $g->where('student_groups.id', $scope->subjectId));
                $stats['sessions_this_week'] = $query->count();
            } elseif ($scope->perspective === TimetablePerspective::Teacher) {
                $query->where('teacher_id', $scope->subjectId);
                $stats['sessions_this_week'] = $query->count();
            } else {
                $stats['sessions_this_week'] = 0;
            }
        }

        if ($canManageExams) {
            $stats['upcoming_exams'] = Exam::query()
                ->where('starts_at', '>=', $now)
                ->whereNotIn('state', ExamState::finished())
                ->count();
        } elseif ($canViewExams) {
            $stats['upcoming_exams'] = Exam::query()
                ->visibleTo($user)
                ->where('starts_at', '>=', $now)
                ->whereNotIn('state', ExamState::finished())
                ->count();
        }

        if ($canViewOwnGrades) {
            $stats['my_grades_count'] = ExamGrade::query()
                ->where('student_id', $user->id)
                ->whereNotNull('final_grade')
                ->count();
        }

        // 2. Upcoming Course Sessions (Next 5)
        $upcomingSessions = [];
        if ($canViewSchedules) {
            $scope = TimetableScope::mine($user);
            $sessionsQuery = CourseSession::query()
                ->with([
                    'module:id,code,name,color_code',
                    'room:id,name,code,building_id',
                    'room.building:id,name',
                    'teacher:id,name',
                    'studentGroups:student_groups.id,student_groups.name,student_groups.code',
                ])
                ->where('ends_at', '>=', $now)
                ->orderBy('starts_at')
                ->orderBy('id');

            if ($scope->perspective === TimetablePerspective::Group && $scope->subjectId !== null) {
                $sessionsQuery->whereHas('studentGroups', fn ($g) => $g->where('student_groups.id', $scope->subjectId));
                $fetchedSessions = $sessionsQuery->take(5)->get();
            } elseif ($scope->perspective === TimetablePerspective::Teacher) {
                $sessionsQuery->where('teacher_id', $scope->subjectId);
                $fetchedSessions = $sessionsQuery->take(5)->get();
            } else {
                $fetchedSessions = collect();
            }

            // Fallback for schedule coordinators/managers who do not teach or belong to a student group
            if ($fetchedSessions->isEmpty() && $canBrowseSchedules) {
                $fetchedSessions = CourseSession::query()
                    ->with([
                        'module:id,code,name,color_code',
                        'room:id,name,code,building_id',
                        'room.building:id,name',
                        'teacher:id,name',
                        'studentGroups:student_groups.id,student_groups.name,student_groups.code',
                    ])
                    ->where('ends_at', '>=', $now)
                    ->orderBy('starts_at')
                    ->orderBy('id')
                    ->take(5)
                    ->get();
            }

            $upcomingSessions = $fetchedSessions->map(function (CourseSession $s) {
                return [
                    'id' => $s->id,
                    'module_name' => $s->module->name,
                    'module_code' => $s->module->code,
                    'color_code' => $s->module->color_code,
                    'starts_at' => $s->starts_at->toIso8601String(),
                    'ends_at' => $s->ends_at->toIso8601String(),
                    'room_name' => $s->room->name,
                    'building_name' => $s->room->building?->name,
                    'teacher_name' => $s->teacher->name,
                    'groups' => $s->studentGroups->pluck('name')->all(),
                ];
            })->values()->all();
        }

        // 3. Upcoming Exams (Next 5)
        $upcomingExams = [];
        if ($canViewExams) {
            $exams = Exam::query()
                ->visibleTo($user)
                ->with([
                    'module:id,code,name,color_code',
                    'examPeriod:id,name',
                    'roomAssignments.room:id,name,code',
                ])
                ->where('ends_at', '>=', $now)
                ->whereNotIn('state', ExamState::finished())
                ->orderBy('starts_at')
                ->orderBy('id')
                ->take(5)
                ->get();

            $upcomingExams = $exams->map(function (Exam $e) {
                return [
                    'id' => $e->id,
                    'module_name' => $e->module->name,
                    'module_code' => $e->module->code,
                    'color_code' => $e->module->color_code,
                    'starts_at' => $e->starts_at->toIso8601String(),
                    'ends_at' => $e->ends_at->toIso8601String(),
                    'state' => $e->state->value,
                    'period_name' => $e->examPeriod->name,
                    'rooms' => $e->roomAssignments->map(fn ($ra) => $ra->room->name)->filter()->values()->all(),
                ];
            })->values()->all();
        }

        // 4. Recent Unread Notifications (Next 5)
        $recentNotifications = $user->unreadNotifications()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'created_at' => $notification->created_at->toIso8601String(),
                    'read_at' => $notification->read_at?->toIso8601String(),
                ];
            })->values()->all();

        return [
            'stats' => $stats,
            'upcomingSessions' => $upcomingSessions,
            'upcomingExams' => $upcomingExams,
            'recentNotifications' => $recentNotifications,
            'permissions' => [
                'canViewSchedules' => $canViewSchedules,
                'canManageSchedules' => $canManageSchedules,
                'canBrowseSchedules' => $canBrowseSchedules,
                'canViewExams' => $canViewExams,
                'canManageExams' => $canManageExams,
                'canManageReferentials' => $canManageReferentials,
                'canReviewUnavailability' => $canReviewUnavailability,
                'canDeclareUnavailability' => $canDeclareUnavailability,
                'canEnterGrades' => $canEnterGrades,
                'canViewOwnGrades' => $canViewOwnGrades,
                'canViewUsers' => $user->hasPermission(Permission::ViewUsers),
                'canImportReferentials' => $user->hasPermission(Permission::ImportReferentials),
            ],
        ];
    }
}
