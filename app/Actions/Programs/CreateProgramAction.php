<?php

namespace App\Actions\Programs;

use App\Enums\ProgramModality;
use App\Models\Department;
use App\Models\Program;
use InvalidArgumentException;

class CreateProgramAction
{
    /**
     * @param array{
     *     department_id: int,
     *     name: string,
     *     code: string,
     *     program_modality: ProgramModality|string,
     *     description?: string|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(array $data): Program
    {
        $name = trim($data['name'] ?? '');
        $code = trim($data['code'] ?? '');

        if ($name === '') {
            throw new InvalidArgumentException('Program name cannot be empty.');
        }

        if ($code === '') {
            throw new InvalidArgumentException('Program code cannot be empty.');
        }

        if (! Department::where('id', $data['department_id'])->exists()) {
            throw new InvalidArgumentException("Department with ID {$data['department_id']} does not exist.");
        }

        $modality = $data['program_modality'] instanceof ProgramModality
            ? $data['program_modality']
            : ProgramModality::tryFrom((string) $data['program_modality']);

        if ($modality === null) {
            throw new InvalidArgumentException("Invalid program modality: {$data['program_modality']}.");
        }

        return Program::create([
            'department_id' => $data['department_id'],
            'name' => $name,
            'code' => strtoupper($code),
            'program_modality' => $modality,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }
}
