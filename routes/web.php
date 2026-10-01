<?php

use App\Http\Controllers\Web\AcademicStructure\AcademicStructureIndexController;
use App\Http\Controllers\Web\Buildings\BuildingStoreController;
use App\Http\Controllers\Web\Buildings\BuildingToggleActiveController;
use App\Http\Controllers\Web\Buildings\BuildingUpdateController;
use App\Http\Controllers\Web\Campuses\CampusStoreController;
use App\Http\Controllers\Web\Campuses\CampusToggleActiveController;
use App\Http\Controllers\Web\Campuses\CampusUpdateController;
use App\Http\Controllers\Web\Departments\DepartmentStoreController;
use App\Http\Controllers\Web\Departments\DepartmentToggleActiveController;
use App\Http\Controllers\Web\Departments\DepartmentUpdateController;
use App\Http\Controllers\Web\Imports\ImportIndexController;
use App\Http\Controllers\Web\Imports\ImportStoreController;
use App\Http\Controllers\Web\Imports\ImportTemplateController;
use App\Http\Controllers\Web\Invitations\InvitationActivateController;
use App\Http\Controllers\Web\Invitations\InvitationShowController;
use App\Http\Controllers\Web\Modules\ModuleIndexController;
use App\Http\Controllers\Web\Modules\ModuleStoreController;
use App\Http\Controllers\Web\Modules\ModuleToggleActiveController;
use App\Http\Controllers\Web\Modules\ModuleUpdateController;
use App\Http\Controllers\Web\Programs\ProgramStoreController;
use App\Http\Controllers\Web\Programs\ProgramToggleActiveController;
use App\Http\Controllers\Web\Programs\ProgramUpdateController;
use App\Http\Controllers\Web\Rooms\RoomIndexController;
use App\Http\Controllers\Web\Rooms\RoomStoreController;
use App\Http\Controllers\Web\Rooms\RoomToggleActiveController;
use App\Http\Controllers\Web\Rooms\RoomUpdateController;
use App\Http\Controllers\Web\StudentGroups\StudentGroupStoreController;
use App\Http\Controllers\Web\StudentGroups\StudentGroupToggleActiveController;
use App\Http\Controllers\Web\StudentGroups\StudentGroupUpdateController;
use App\Http\Controllers\Web\Unavailabilities\UnavailabilityDestroyController;
use App\Http\Controllers\Web\Unavailabilities\UnavailabilityIndexController;
use App\Http\Controllers\Web\Unavailabilities\UnavailabilityReviewController;
use App\Http\Controllers\Web\Unavailabilities\UnavailabilityReviewIndexController;
use App\Http\Controllers\Web\Unavailabilities\UnavailabilityStoreController;
use App\Http\Controllers\Web\Unavailabilities\UnavailabilityUpdateController;
use App\Http\Controllers\Web\Users\UserIndexController;
use App\Http\Controllers\Web\Users\UserResendInvitationController;
use App\Http\Controllers\Web\Users\UserStoreController;
use App\Http\Controllers\Web\Users\UserTemporaryPasswordController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Invitation Token activation (ADR 0007): signed, single-use, 72-hour links.
Route::get('invitations/{token}', InvitationShowController::class)->name('invitations.show');
Route::post('invitations/{token}', InvitationActivateController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('invitations.activate');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Physical Referentials (Campuses, Buildings, Rooms)
    Route::get('rooms', RoomIndexController::class)->name('rooms.index');
    Route::post('rooms', RoomStoreController::class)->name('rooms.store');
    Route::put('rooms/{room}', RoomUpdateController::class)->name('rooms.update');
    Route::patch('rooms/{room}/toggle-active', RoomToggleActiveController::class)->name('rooms.toggle-active');

    Route::post('campuses', CampusStoreController::class)->name('campuses.store');
    Route::put('campuses/{campus}', CampusUpdateController::class)->name('campuses.update');
    Route::patch('campuses/{campus}/toggle-active', CampusToggleActiveController::class)->name('campuses.toggle-active');

    Route::post('buildings', BuildingStoreController::class)->name('buildings.store');
    Route::put('buildings/{building}', BuildingUpdateController::class)->name('buildings.update');
    Route::patch('buildings/{building}/toggle-active', BuildingToggleActiveController::class)->name('buildings.toggle-active');

    // Academic Structure Referentials (Departments, Programs with Modality, Student Groups)
    Route::get('academic-structure', AcademicStructureIndexController::class)->name('academic-structure.index');

    Route::post('departments', DepartmentStoreController::class)->name('departments.store');
    Route::put('departments/{department}', DepartmentUpdateController::class)->name('departments.update');
    Route::patch('departments/{department}/toggle-active', DepartmentToggleActiveController::class)->name('departments.toggle-active');

    Route::post('programs', ProgramStoreController::class)->name('programs.store');
    Route::put('programs/{program}', ProgramUpdateController::class)->name('programs.update');
    Route::patch('programs/{program}/toggle-active', ProgramToggleActiveController::class)->name('programs.toggle-active');

    Route::post('student-groups', StudentGroupStoreController::class)->name('student-groups.store');
    Route::put('student-groups/{student_group}', StudentGroupUpdateController::class)->name('student-groups.update');
    Route::patch('student-groups/{student_group}/toggle-active', StudentGroupToggleActiveController::class)->name('student-groups.toggle-active');

    // Teacher Unavailability declarations and coordinator review (Part 02 / Ticket 01)
    Route::get('unavailabilities', UnavailabilityIndexController::class)->name('unavailabilities.index');
    Route::post('unavailabilities', UnavailabilityStoreController::class)->name('unavailabilities.store');
    Route::put('unavailabilities/{unavailability}', UnavailabilityUpdateController::class)->name('unavailabilities.update');
    Route::delete('unavailabilities/{unavailability}', UnavailabilityDestroyController::class)->name('unavailabilities.destroy');
    Route::get('unavailability-reviews', UnavailabilityReviewIndexController::class)->name('unavailability-reviews.index');
    Route::patch('unavailability-reviews/{unavailability}', UnavailabilityReviewController::class)->name('unavailability-reviews.update');

    // Modules Catalog & Syllabus
    Route::get('modules', ModuleIndexController::class)->name('modules.index');
    Route::post('modules', ModuleStoreController::class)->name('modules.store');
    Route::put('modules/{module}', ModuleUpdateController::class)->name('modules.update');
    Route::patch('modules/{module}/toggle-active', ModuleToggleActiveController::class)->name('modules.toggle-active');

    // Bulk Spreadsheet Imports (Rooms, Modules, Teachers, Students)
    Route::get('imports', ImportIndexController::class)->name('imports.index');
    Route::get('imports/{type}/template', ImportTemplateController::class)->name('imports.template');
    Route::post('imports/{type}', ImportStoreController::class)->name('imports.store');

    // User Provisioning & Invitation Tokens
    Route::get('users', UserIndexController::class)->name('users.index');
    Route::post('users', UserStoreController::class)->name('users.store');
    Route::post('users/{user}/resend-invitation', UserResendInvitationController::class)->name('users.resend-invitation');
    Route::post('users/{user}/temporary-password', UserTemporaryPasswordController::class)->name('users.temporary-password');
});

require __DIR__.'/settings.php';
