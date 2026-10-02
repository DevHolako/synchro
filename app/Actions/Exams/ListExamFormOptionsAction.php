<?php

namespace App\Actions\Exams;

use App\Models\Module;
use App\Models\Program;
use App\Models\StudentGroup;

/**
 * What the exam form and filters let a coordinator pick: active programs, modules and groups.
 */
class ListExamFormOptionsAction
{
    /**
     * @return array{
     *     programs: list<array{id: int, code: string, name: string}>,
     *     modules: list<array{id: int, program_id: int, code: string, name: string}>,
     *     groups: list<array{id: int, program_id: int, name: string}>
     * }
     */
    public function execute(): array
    {
        return [
            'programs' => array_values(Program::query()
                ->active()
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (Program $program): array => ['id' => $program->id, 'code' => $program->code, 'name' => $program->name])
                ->all()),
            'modules' => array_values(Module::query()
                ->active()
                ->orderBy('code')
                ->get(['id', 'program_id', 'code', 'name'])
                ->map(fn (Module $module): array => [
                    'id' => $module->id,
                    'program_id' => $module->program_id,
                    'code' => $module->code,
                    'name' => $module->name,
                ])
                ->all()),
            'groups' => array_values(StudentGroup::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'program_id', 'name'])
                ->map(fn (StudentGroup $group): array => ['id' => $group->id, 'program_id' => $group->program_id, 'name' => $group->name])
                ->all()),
        ];
    }
}
