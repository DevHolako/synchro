<?php

namespace App\Enums;

enum Permission: string
{
    // Referential management permissions
    case ViewReferentials = 'view:referentials';
    case ManageReferentials = 'manage:referentials';

    // Room permissions
    case ViewRooms = 'view:rooms';
    case CreateRooms = 'create:rooms';
    case UpdateRooms = 'update:rooms';
    case DeleteRooms = 'delete:rooms';

    // Campus permissions
    case ViewCampuses = 'view:campuses';
    case CreateCampuses = 'create:campuses';
    case UpdateCampuses = 'update:campuses';
    case DeleteCampuses = 'delete:campuses';

    // Building permissions
    case ViewBuildings = 'view:buildings';
    case CreateBuildings = 'create:buildings';
    case UpdateBuildings = 'update:buildings';
    case DeleteBuildings = 'delete:buildings';

    // Department permissions
    case ViewDepartments = 'view:departments';
    case CreateDepartments = 'create:departments';
    case UpdateDepartments = 'update:departments';
    case DeleteDepartments = 'delete:departments';

    // Program permissions
    case ViewPrograms = 'view:programs';
    case CreatePrograms = 'create:programs';
    case UpdatePrograms = 'update:programs';
    case DeletePrograms = 'delete:programs';
    case ManagePrograms = 'manage:programs';

    // Student Group permissions
    case ViewStudentGroups = 'view:student-groups';
    case CreateStudentGroups = 'create:student-groups';
    case UpdateStudentGroups = 'update:student-groups';
    case DeleteStudentGroups = 'delete:student-groups';

    // Module permissions
    case ViewModules = 'view:modules';
    case CreateModules = 'create:modules';
    case UpdateModules = 'update:modules';
    case DeleteModules = 'delete:modules';
    case ManageModules = 'manage:modules';

    // Planning & Scheduling permissions
    case ViewSchedules = 'view:schedules';
    case ManageSchedules = 'manage:schedules';
    case OverrideSoftConflicts = 'override:soft-conflicts';

    // Examination logistics permissions
    case ViewExams = 'view:exams';
    case ManageExams = 'manage:exams';

    // Teacher unavailability permissions
    case DeclareUnavailability = 'declare:unavailability';
    case ReviewUnavailability = 'review:unavailability';

    // Grade deliberation permissions
    case EnterGrades = 'enter:grades';
    case LockGrades = 'lock:grades';

    // Bulk spreadsheet import permissions
    case ImportReferentials = 'import:referentials';

    // Infrastructure monitoring permissions (Horizon queue dashboard)
    case MonitorQueues = 'monitor:queues';

    // User management permissions
    case ViewUsers = 'view:users';
    case ProvisionUsers = 'provision:users';
    case ManageUsers = 'manage:users';
}
