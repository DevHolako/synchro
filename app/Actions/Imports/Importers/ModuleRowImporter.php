<?php

namespace App\Actions\Imports\Importers;

use App\Actions\Modules\CreateModuleAction;
use App\Exceptions\ImportRowException;
use App\Models\Module;
use App\Models\Program;
use App\Models\User;

class ModuleRowImporter implements RowImporter
{
    private const string DEFAULT_COLOR = '#3B82F6';

    public function __construct(private readonly CreateModuleAction $createModule) {}

    public function rules(): array
    {
        return [
            'department_code' => ['required', 'string', 'max:50'],
            'program_code' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'total_hours' => ['required', 'integer', 'min:1'],
            'lecture_hours' => ['nullable', 'integer', 'min:0'],
            'tp_hours' => ['nullable', 'integer', 'min:0'],
            'color_code' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'teacher_email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function uniqueKeys(array $row): array
    {
        return [
            'code' => mb_strtoupper(implode('|', [$row['department_code'] ?? '', $row['program_code'] ?? '', $row['code'] ?? ''])),
        ];
    }

    public function prepare(array $row): array
    {
        $lectureHours = (int) ($row['lecture_hours'] ?? 0);
        $tpHours = (int) ($row['tp_hours'] ?? 0);

        if ($lectureHours + $tpHours > (int) $row['total_hours']) {
            throw new ImportRowException('lecture_hours', __('messages.import_hours_exceed_total'));
        }

        $program = Program::query()
            ->where('code', $row['program_code'])
            ->whereHas('department', fn ($query) => $query->where('code', $row['department_code']))
            ->first();

        if ($program === null) {
            throw new ImportRowException('program_code', __('messages.import_program_not_found', [
                'program' => $row['program_code'],
                'department' => $row['department_code'],
            ]));
        }

        $code = mb_strtoupper((string) $row['code']);

        if (Module::query()->where('program_id', $program->id)->where('code', $code)->exists()) {
            throw new ImportRowException('code', __('messages.import_module_exists', ['code' => $code]));
        }

        $teacherId = null;

        if (! empty($row['teacher_email'])) {
            $teacherId = User::teachers()->where('email', mb_strtolower($row['teacher_email']))->value('id');

            if ($teacherId === null) {
                throw new ImportRowException('teacher_email', __('messages.import_teacher_not_found', ['email' => $row['teacher_email']]));
            }
        }

        return [
            'program_id' => $program->id,
            'teacher_id' => $teacherId,
            'name' => $row['name'],
            'code' => $code,
            'total_hours' => (int) $row['total_hours'],
            'lecture_hours' => $lectureHours,
            'tp_hours' => $tpHours,
            'color_code' => $row['color_code'] ?? self::DEFAULT_COLOR,
            'description' => $row['description'] ?? null,
        ];
    }

    public function persist(array $payload, User $actor): void
    {
        $this->createModule->execute($payload);
    }
}
