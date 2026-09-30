<?php

namespace App\Http\Controllers\Web\AcademicStructure;

use App\Enums\ProgramModality;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Program;
use App\Models\StudentGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicStructureIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! $request->user()?->can('viewAny', Department::class)
            && ! $request->user()?->can('viewAny', Program::class)
        ) {
            abort(403, 'Unauthorized to view academic structure.');
        }

        $search = $request->input('search');
        $departmentId = $request->integer('department_id');
        $modalityInput = $request->input('program_modality');
        $activeStatus = $request->input('is_active', 'all');
        $academicYear = $request->input('academic_year');

        // 1. Departments Query
        $deptQuery = Department::query()->withCount(['programs', 'studentGroups']);
        if ($search) {
            $deptQuery->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }
        if ($activeStatus !== 'all' && $activeStatus !== null) {
            $deptQuery->where('is_active', (bool) $activeStatus);
        }
        $departments = $deptQuery->orderBy('name')->get();

        // 2. Programs Query
        $progQuery = Program::query()
            ->with(['department'])
            ->withCount('studentGroups')
            ->withSum('studentGroups', 'expected_headcount');

        if ($search) {
            $progQuery->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('department', fn (Builder $d) => $d->where('name', 'like', "%{$search}%"));
            });
        }
        if ($departmentId) {
            $progQuery->where('department_id', $departmentId);
        }
        if ($modalityInput && in_array($modalityInput, ['temps_amenage', 'formation_initiale'], true)) {
            $progQuery->where('program_modality', $modalityInput);
        }
        if ($activeStatus !== 'all' && $activeStatus !== null) {
            $progQuery->where('is_active', (bool) $activeStatus);
        }
        $programs = $progQuery->orderBy('name')->get();

        // 3. Student Groups Query
        $groupQuery = StudentGroup::query()->with(['program.department', 'campus']);
        if ($search) {
            $groupQuery->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('program', function (Builder $p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                            ->orWhereHas('department', fn (Builder $d) => $d->where('name', 'like', "%{$search}%"));
                    });
            });
        }
        if ($departmentId) {
            $groupQuery->whereHas('program', fn (Builder $p) => $p->where('department_id', $departmentId));
        }
        if ($modalityInput && in_array($modalityInput, ['temps_amenage', 'formation_initiale'], true)) {
            $groupQuery->whereHas('program', fn (Builder $p) => $p->where('program_modality', $modalityInput));
        }
        if ($academicYear) {
            $groupQuery->where('academic_year', $academicYear);
        }
        if ($activeStatus !== 'all' && $activeStatus !== null) {
            $groupQuery->where('is_active', (bool) $activeStatus);
        }
        $studentGroups = $groupQuery->orderBy('academic_year', 'desc')->orderBy('name')->get();

        $campuses = Campus::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'city']);

        $stats = [
            'total_departments' => Department::count(),
            'total_programs' => Program::count(),
            'programs_formation_initiale' => Program::where('program_modality', ProgramModality::FormationInitiale)->count(),
            'programs_temps_amenage' => Program::where('program_modality', ProgramModality::TempsAmenage)->count(),
            'total_groups' => StudentGroup::count(),
            'total_expected_headcount' => (int) StudentGroup::where('is_active', true)->sum('expected_headcount'),
        ];

        return Inertia::render('academic-structure/index', [
            'departments' => $departments,
            'programs' => $programs,
            'studentGroups' => $studentGroups,
            'campuses' => $campuses,
            'filters' => [
                'search' => $search ?? '',
                'department_id' => $departmentId ?: '',
                'program_modality' => $modalityInput ?? 'all',
                'academic_year' => $academicYear ?? '',
                'is_active' => $activeStatus,
            ],
            'stats' => $stats,
        ]);
    }
}
