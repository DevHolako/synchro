<?php

namespace App\Actions\StudentGroups;

use App\Models\Campus;
use App\Models\Program;
use App\Models\StudentGroup;
use InvalidArgumentException;

class CreateStudentGroupAction
{
    /**
     * @param array{
     *     program_id: int,
     *     campus_id?: int|null,
     *     name: string,
     *     code?: string|null,
     *     academic_year: string,
     *     expected_headcount: int,
     *     is_active?: bool
     * } $data
     */
    public function execute(array $data): StudentGroup
    {
        $name = trim($data['name'] ?? '');
        $academicYear = trim($data['academic_year'] ?? '');
        $headcount = (int) ($data['expected_headcount'] ?? 0);

        if ($name === '') {
            throw new InvalidArgumentException('Student group name cannot be empty.');
        }

        if ($academicYear === '') {
            throw new InvalidArgumentException('Academic year cannot be empty.');
        }

        if ($headcount <= 0) {
            throw new InvalidArgumentException('Expected headcount must be greater than 0.');
        }

        if (! Program::where('id', $data['program_id'])->exists()) {
            throw new InvalidArgumentException("Program with ID {$data['program_id']} does not exist.");
        }

        if (! empty($data['campus_id']) && ! Campus::where('id', $data['campus_id'])->exists()) {
            throw new InvalidArgumentException("Campus with ID {$data['campus_id']} does not exist.");
        }

        return StudentGroup::create([
            'program_id' => $data['program_id'],
            'campus_id' => $data['campus_id'] ?? null,
            'name' => $name,
            'code' => ! empty($data['code']) ? strtoupper(trim($data['code'])) : null,
            'academic_year' => $academicYear,
            'expected_headcount' => $headcount,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }
}
