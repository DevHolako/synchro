<?php

namespace App\Actions\StudentGroups;

use App\Models\Campus;
use App\Models\Program;
use App\Models\StudentGroup;
use InvalidArgumentException;

class UpdateStudentGroupAction
{
    /**
     * @param array{
     *     program_id?: int,
     *     campus_id?: int|null,
     *     name?: string,
     *     code?: string|null,
     *     academic_year?: string,
     *     expected_headcount?: int,
     *     is_active?: bool
     * } $data
     */
    public function execute(StudentGroup $studentGroup, array $data): StudentGroup
    {
        $payload = [];

        if (array_key_exists('program_id', $data)) {
            if (! Program::where('id', $data['program_id'])->exists()) {
                throw new InvalidArgumentException("Program with ID {$data['program_id']} does not exist.");
            }
            $payload['program_id'] = $data['program_id'];
        }

        if (array_key_exists('campus_id', $data)) {
            if ($data['campus_id'] !== null && ! Campus::where('id', $data['campus_id'])->exists()) {
                throw new InvalidArgumentException("Campus with ID {$data['campus_id']} does not exist.");
            }
            $payload['campus_id'] = $data['campus_id'];
        }

        if (array_key_exists('name', $data)) {
            $name = trim($data['name']);
            if ($name === '') {
                throw new InvalidArgumentException('Student group name cannot be empty.');
            }
            $payload['name'] = $name;
        }

        if (array_key_exists('code', $data)) {
            $payload['code'] = ! empty($data['code']) ? strtoupper(trim($data['code'])) : null;
        }

        if (array_key_exists('academic_year', $data)) {
            $academicYear = trim($data['academic_year']);
            if ($academicYear === '') {
                throw new InvalidArgumentException('Academic year cannot be empty.');
            }
            $payload['academic_year'] = $academicYear;
        }

        if (array_key_exists('expected_headcount', $data)) {
            $headcount = (int) $data['expected_headcount'];
            if ($headcount <= 0) {
                throw new InvalidArgumentException('Expected headcount must be greater than 0.');
            }
            $payload['expected_headcount'] = $headcount;
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        $studentGroup->update($payload);

        return $studentGroup;
    }
}
