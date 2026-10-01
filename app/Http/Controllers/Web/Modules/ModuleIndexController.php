<?php

namespace App\Http\Controllers\Web\Modules;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ModuleIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', Module::class);

        $search = $request->input('search');
        $programId = $request->integer('program_id');
        $teacherId = $request->integer('teacher_id');
        $activeStatus = $request->input('is_active', 'all');

        $query = Module::query()->with(['program.department', 'teacher']);

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('program', function (Builder $p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                            ->orWhereHas('department', fn (Builder $d) => $d->where('name', 'like', "%{$search}%"));
                    })
                    ->orWhereHas('teacher', fn (Builder $t) => $t->where('name', 'like', "%{$search}%"));
            });
        }

        if ($programId) {
            $query->where('program_id', $programId);
        }

        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }

        if ($activeStatus !== 'all' && $activeStatus !== null) {
            $query->where('is_active', (bool) $activeStatus);
        }

        $modules = $query->orderBy('name')->get();

        $programs = Program::with('department')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'department_id', 'name', 'code', 'program_modality']);

        $teachers = User::teachers()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $stats = [
            'total_modules' => Module::count(),
            'active_modules' => Module::where('is_active', true)->count(),
            'total_syllabus_hours' => (int) Module::where('is_active', true)->sum('total_hours'),
            'total_lecture_hours' => (int) Module::where('is_active', true)->sum('lecture_hours'),
            'total_tp_hours' => (int) Module::where('is_active', true)->sum('tp_hours'),
            'assigned_modules' => Module::where('is_active', true)->whereNotNull('teacher_id')->count(),
        ];

        return Inertia::render('modules/index', [
            'modules' => $modules,
            'programs' => $programs,
            'teachers' => $teachers,
            'filters' => [
                'search' => $search ?? '',
                'program_id' => $programId ?: '',
                'teacher_id' => $teacherId ?: '',
                'is_active' => $activeStatus,
            ],
            'stats' => $stats,
        ]);
    }
}
