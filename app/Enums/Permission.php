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

    // Academic structure permissions (for subsequent tickets)
    case ViewPrograms = 'view:programs';
    case ManagePrograms = 'manage:programs';
    case ViewModules = 'view:modules';
    case ManageModules = 'manage:modules';

    // Planning & Scheduling permissions
    case ViewSchedules = 'view:schedules';
    case ManageSchedules = 'manage:schedules';

    // Examination logistics permissions
    case ViewExams = 'view:exams';
    case ManageExams = 'manage:exams';

    // Grade deliberation permissions
    case EnterGrades = 'enter:grades';
    case LockGrades = 'lock:grades';

    // User management permissions
    case ManageUsers = 'manage:users';
}
