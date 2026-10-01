<?php

namespace App\Actions\Modules;

use App\Models\Module;
use App\Models\Program;
use App\Models\User;
use InvalidArgumentException;

class CreateModuleAction
{
    /**
     * @param array{
     *     program_id: int,
     *     teacher_id?: int|null,
     *     name: string,
     *     code: string,
     *     total_hours: int,
     *     lecture_hours?: int,
     *     tp_hours?: int,
     *     color_code?: string|null,
     *     description?: string|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(array $data): Module
    {
        $name = trim($data['name']);
        $code = trim($data['code']);
        $totalHours = (int) $data['total_hours'];
        $lectureHours = (int) ($data['lecture_hours'] ?? 0);
        $tpHours = (int) ($data['tp_hours'] ?? 0);
        $colorCode = trim($data['color_code'] ?? '#3B82F6');

        if ($name === '') {
            throw new InvalidArgumentException('Module name cannot be empty.');
        }

        if ($code === '') {
            throw new InvalidArgumentException('Module code cannot be empty.');
        }

        if ($totalHours <= 0) {
            throw new InvalidArgumentException('Total syllabus hours must be greater than 0.');
        }

        if ($lectureHours < 0 || $tpHours < 0) {
            throw new InvalidArgumentException('Lecture and practical (TP) hours cannot be negative.');
        }

        if ($lectureHours + $tpHours > $totalHours) {
            throw new InvalidArgumentException('The sum of lecture hours and TP hours cannot exceed total syllabus hours.');
        }

        if (! preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $colorCode)) {
            throw new InvalidArgumentException("Invalid hex color code: {$colorCode}.");
        }

        if (! Program::where('id', $data['program_id'])->exists()) {
            throw new InvalidArgumentException("Program with ID {$data['program_id']} does not exist.");
        }

        if (! empty($data['teacher_id']) && ! User::teachers()->whereKey($data['teacher_id'])->exists()) {
            throw new InvalidArgumentException("User {$data['teacher_id']} is not a teacher.");
        }

        return Module::create([
            'program_id' => $data['program_id'],
            'teacher_id' => $data['teacher_id'] ?? null,
            'name' => $name,
            'code' => strtoupper($code),
            'total_hours' => $totalHours,
            'lecture_hours' => $lectureHours,
            'tp_hours' => $tpHours,
            'color_code' => $colorCode,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }
}
