<?php

namespace App\Http\Controllers\Web\Users;

use App\Enums\AccountStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\StudentGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! $request->user()?->can('viewAny', User::class)) {
            abort(403);
        }

        $search = $request->string('search')->trim()->value();
        $role = UserRole::tryFrom($request->string('role')->value());
        $status = AccountStatus::tryFrom($request->string('status')->value());

        $users = User::query()
            ->with([
                'latestInvitation',
                'teacherProfile.department:id,name,code',
                'studentProfile.studentGroup:id,name,code',
            ])
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($role, fn (Builder $query) => $query->where('role', $role))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $actor = $request->user();

        return Inertia::render('users/index', [
            'users' => $users,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'studentGroups' => StudentGroup::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'academic_year']),
            'filters' => [
                'search' => $search,
                'role' => $role->value ?? '',
                'status' => $status->value ?? '',
            ],
            'stats' => [
                'total' => User::count(),
                'active' => User::where('status', AccountStatus::Active)->count(),
                'invited' => User::where('status', AccountStatus::Invited)->count(),
                'teachers' => User::where('role', UserRole::Teacher)->count(),
                'students' => User::where('role', UserRole::Student)->count(),
            ],
            'can' => [
                'provision' => $actor->can('create', User::class),
                'issue_temporary_password' => $actor->hasPermission(Permission::ManageUsers),
            ],
        ]);
    }
}
