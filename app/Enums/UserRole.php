<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Coordinator = 'coordinator';
    case Teacher = 'teacher';
    case Student = 'student';

    /**
     * Get human-friendly label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Coordinator => 'Coordinator',
            self::Teacher => 'Teacher',
            self::Student => 'Student',
        };
    }

    /**
     * Get the group of permissions bundled under this role.
     * Roles are strictly collections of permissions.
     *
     * @return array<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Administrator => Permission::cases(),

            self::Coordinator => [
                Permission::ViewReferentials,
                Permission::ManageReferentials,
                Permission::ViewRooms,
                Permission::CreateRooms,
                Permission::UpdateRooms,
                Permission::DeleteRooms,
                Permission::ViewCampuses,
                Permission::CreateCampuses,
                Permission::UpdateCampuses,
                Permission::DeleteCampuses,
                Permission::ViewBuildings,
                Permission::CreateBuildings,
                Permission::UpdateBuildings,
                Permission::DeleteBuildings,
                Permission::ViewDepartments,
                Permission::CreateDepartments,
                Permission::UpdateDepartments,
                Permission::DeleteDepartments,
                Permission::ViewPrograms,
                Permission::CreatePrograms,
                Permission::UpdatePrograms,
                Permission::DeletePrograms,
                Permission::ManagePrograms,
                Permission::ViewStudentGroups,
                Permission::CreateStudentGroups,
                Permission::UpdateStudentGroups,
                Permission::DeleteStudentGroups,
                Permission::ViewModules,
                Permission::ManageModules,
                Permission::ViewSchedules,
                Permission::ManageSchedules,
                Permission::ViewExams,
                Permission::ManageExams,
            ],

            self::Teacher => [
                Permission::ViewReferentials,
                Permission::ViewRooms,
                Permission::ViewCampuses,
                Permission::ViewBuildings,
                Permission::ViewDepartments,
                Permission::ViewPrograms,
                Permission::ViewStudentGroups,
                Permission::ViewModules,
                Permission::ViewSchedules,
                Permission::ViewExams,
                Permission::EnterGrades,
            ],

            self::Student => [
                Permission::ViewSchedules,
                Permission::ViewExams,
            ],
        };
    }

    /**
     * Check if this role includes a specific permission.
     */
    public function hasPermission(Permission|string $permission): bool
    {
        $permissionValue = $permission instanceof Permission ? $permission : Permission::tryFrom($permission);

        if ($permissionValue === null) {
            return false;
        }

        return in_array($permissionValue, $this->permissions(), true);
    }
}
