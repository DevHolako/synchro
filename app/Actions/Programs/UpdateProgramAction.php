<?php

namespace App\Actions\Programs;

use App\Enums\ProgramModality;
use App\Models\Department;
use App\Models\Program;
use InvalidArgumentException;

class UpdateProgramAction
{
    /**
     * @param array{
     *     department_id?: int,
     *     name?: string,
     *     code?: string,
     *     program_modality?: ProgramModality|string,
     *     description?: string|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(Program $program, array $data): Program
    {
        $payload = [];

        if (array_key_exists('department_id', $data)) {
            if (! Department::where('id', $data['department_id'])->exists()) {
                throw new InvalidArgumentException("Department with ID {$data['department_id']} does not exist.");
            }
            $payload['department_id'] = $data['department_id'];
        }

        if (array_key_exists('name', $data)) {
            $name = trim($data['name']);
            if ($name === '') {
                throw new InvalidArgumentException('Program name cannot be empty.');
            }
            $payload['name'] = $name;
        }

        if (array_key_exists('code', $data)) {
            $code = trim($data['code']);
            if ($code === '') {
                throw new InvalidArgumentException('Program code cannot be empty.');
            }
            $payload['code'] = strtoupper($code);
        }

        if (array_key_exists('program_modality', $data)) {
            $modality = $data['program_modality'] instanceof ProgramModality
                ? $data['program_modality']
                : ProgramModality::tryFrom((string) $data['program_modality']);

            if ($modality === null) {
                throw new InvalidArgumentException("Invalid program modality: {$data['program_modality']}.");
            }
            $payload['program_modality'] = $modality;
        }

        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'];
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        $program->update($payload);

        return $program;
    }
}
