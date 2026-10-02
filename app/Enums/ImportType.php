<?php

namespace App\Enums;

use App\Models\Module;
use App\Models\Room;
use App\Models\User;

enum ImportType: string
{
    case Rooms = 'rooms';
    case Modules = 'modules';
    case Teachers = 'teachers';
    case Students = 'students';

    /**
     * Spreadsheet header columns, in template order.
     *
     * @return list<string>
     */
    public function columns(): array
    {
        return match ($this) {
            self::Rooms => [
                'campus_code', 'building', 'name', 'code', 'floor', 'course_capacity', 'exam_capacity',
                'has_projector', 'is_lab', 'has_computers', 'has_sound_system',
            ],
            self::Modules => [
                'department_code', 'program_code', 'code', 'name', 'total_hours', 'lecture_hours', 'tp_hours',
                'color_code', 'teacher_email', 'description',
            ],
            self::Teachers => ['name', 'email', 'department_code', 'employee_number', 'phone'],
            self::Students => ['last_name', 'first_name', 'email', 'group_code', 'student_number', 'phone'],
        };
    }

    /**
     * Columns that must be present in the header row.
     *
     * @return list<string>
     */
    public function requiredColumns(): array
    {
        return match ($this) {
            self::Rooms => ['campus_code', 'building', 'name', 'course_capacity', 'exam_capacity'],
            self::Modules => ['department_code', 'program_code', 'code', 'name', 'total_hours'],
            self::Teachers => ['name', 'email'],
            self::Students => ['last_name', 'first_name', 'email', 'group_code'],
        };
    }

    /**
     * An illustrative row written into downloadable templates.
     *
     * @return list<string>
     */
    public function exampleRow(): array
    {
        return match ($this) {
            self::Rooms => ['CASA', 'Bloc A', 'Salle A101', 'A101', '1', '40', '20', 'oui', 'non', 'non', 'non'],
            self::Modules => ['ISI', '1CI', 'ALGO-101', 'Algorithmique avancée', '40', '24', '16', '#3B82F6', '', ''],
            self::Teachers => ['Amina El Idrissi', 'amina.elidrissi@isga.ma', 'ISI', 'ENS-00042', '0612345678'],
            self::Students => ['Benali', 'Youssef', 'youssef.benali@isga.ma', '1CI-G1', 'ETU-123456', ''],
        };
    }

    /**
     * The policy ability that must be granted to import this referential.
     *
     * @return array{0: string, 1: class-string}
     */
    public function ability(): array
    {
        return match ($this) {
            self::Rooms => ['create', Room::class],
            self::Modules => ['create', Module::class],
            self::Teachers, self::Students => ['create', User::class],
        };
    }
}
