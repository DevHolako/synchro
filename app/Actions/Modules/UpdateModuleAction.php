<?php

namespace App\Actions\Modules;

use App\Models\Module;
use App\Models\Program;
use App\Models\User;
use InvalidArgumentException;

class UpdateModuleAction
{
    /**
     * @param array{
     *     program_id?: int,
     *     teacher_id?: int|null,
     *     name?: string,
     *     code?: string,
     *     total_hours?: int,
     *     lecture_hours?: int,
     *     tp_hours?: int,
     *     color_code?: string,
     *     description?: string|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(Module $module, array $data): Module
    {
        $payload = [];

        if (array_key_exists('program_id', $data)) {
            if (! Program::where('id', $data['program_id'])->exists()) {
                throw new InvalidArgumentException("Program with ID {$data['program_id']} does not exist.");
            }
            $payload['program_id'] = $data['program_id'];
        }

        if (array_key_exists('teacher_id', $data)) {
            if ($data['teacher_id'] !== null && ! User::where('id', $data['teacher_id'])->exists()) {
                throw new InvalidArgumentException("Teacher with ID {$data['teacher_id']} does not exist.");
            }
            $payload['teacher_id'] = $data['teacher_id'];
        }

        if (array_key_exists('name', $data)) {
            $name = trim($data['name']);
            if ($name === '') {
                throw new InvalidArgumentException('Module name cannot be empty.');
            }
            $payload['name'] = $name;
        }

        if (array_key_exists('code', $data)) {
            $code = trim($data['code']);
            if ($code === '') {
                throw new InvalidArgumentException('Module code cannot be empty.');
            }
            $payload['code'] = strtoupper($code);
        }

        $totalHours = array_key_exists('total_hours', $data) ? (int) $data['total_hours'] : $module->total_hours;
        $lectureHours = array_key_exists('lecture_hours', $data) ? (int) $data['lecture_hours'] : $module->lecture_hours;
        $tpHours = array_key_exists('tp_hours', $data) ? (int) $data['tp_hours'] : $module->tp_hours;

        if ($totalHours <= 0) {
            throw new InvalidArgumentException('Total syllabus hours must be greater than 0.');
        }

        if ($lectureHours < 0 || $tpHours < 0) {
            throw new InvalidArgumentException('Lecture and practical (TP) hours cannot be negative.');
        }

        if ($lectureHours + $tpHours > $totalHours) {
            throw new InvalidArgumentException('The sum of lecture hours and TP hours cannot exceed total syllabus hours.');
        }

        if (array_key_exists('total_hours', $data)) {
            $payload['total_hours'] = $totalHours;
        }
        if (array_key_exists('lecture_hours', $data)) {
            $payload['lecture_hours'] = $lectureHours;
        }
        if (array_key_exists('tp_hours', $data)) {
            $payload['tp_hours'] = $tpHours;
        }

        if (array_key_exists('color_code', $data)) {
            $color = trim($data['color_code']);
            if (! preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $color)) {
                throw new InvalidArgumentException("Invalid hex color code: {$color}.");
            }
            $payload['color_code'] = $color;
        }

        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'];
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        $module->update($payload);

        return $module;
    }
}
