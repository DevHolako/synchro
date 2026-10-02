<?php

use App\Http\Controllers\Web\AcademicStructure\AcademicStructureIndexController;
use App\Http\Controllers\Web\Attendance\AttendanceShowController;
use App\Http\Controllers\Web\Attendance\AttendanceUpdateController;
use App\Http\Controllers\Web\Buildings\BuildingStoreController;
use App\Http\Controllers\Web\Buildings\BuildingToggleActiveController;
use App\Http\Controllers\Web\Buildings\BuildingUpdateController;
use App\Http\Controllers\Web\CalendarFeeds\CalendarFeedDestroyController;
use App\Http\Controllers\Web\CalendarFeeds\CalendarFeedShowController;
use App\Http\Controllers\Web\CalendarFeeds\CalendarFeedStoreController;
use App\Http\Controllers\Web\Campuses\CampusStoreController;
use App\Http\Controllers\Web\Campuses\CampusToggleActiveController;
use App\Http\Controllers\Web\Campuses\CampusUpdateController;
use App\Http\Controllers\Web\CourseSessions\CourseSessionBatchCheckController;
use App\Http\Controllers\Web\CourseSessions\CourseSessionBatchStoreController;
use App\Http\Controllers\Web\CourseSessions\CourseSessionCheckController;
use App\Http\Controllers\Web\CourseSessions\CourseSessionDestroyController;
use App\Http\Controllers\Web\CourseSessions\CourseSessionRescheduleController;
use App\Http\Controllers\Web\CourseSessions\CourseSessionStoreController;
use App\Http\Controllers\Web\CourseSessions\CourseSessionUpdateController;
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
use App\Http\Controllers\Web\Timetable\TimetableIndexController;
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

// Private iCal subscriptions (Part 03 / Ticket 05, ADR 0010): the token is the credential.
Route::get('feeds/calendar/{token}.ics', CalendarFeedShowController::class)
    ->where('token', '[A-Za-z0-9]+')
    ->middleware('throttle:60,1')
    ->name('calendar-feeds.show');

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

    // Timetable calendar by perspective (Part 03 / Ticket 01)
    Route::get('timetable', TimetableIndexController::class)->name('timetable.index');

    // Course sessions and synchronous conflict detection (Part 02 / Ticket 02, ADR 0002)
    Route::post('course-sessions/check', CourseSessionCheckController::class)->name('course-sessions.check');
    Route::post('course-sessions', CourseSessionStoreController::class)->name('course-sessions.store');

    // Batch scheduling wizard (Part 03 / Ticket 02)
    Route::post('course-sessions/batch/check', CourseSessionBatchCheckController::class)->name('course-sessions.batch.check');
    Route::post('course-sessions/batch', CourseSessionBatchStoreController::class)->name('course-sessions.batch.store');

    Route::put('course-sessions/{session}', CourseSessionUpdateController::class)->name('course-sessions.update');
    Route::delete('course-sessions/{session}', CourseSessionDestroyController::class)->name('course-sessions.destroy');
    Route::patch('course-sessions/{session}/reschedule', CourseSessionRescheduleController::class)->name('course-sessions.reschedule');

    // The user's own calendar subscription link (Part 03 / Ticket 05)
    Route::post('calendar-feed', CalendarFeedStoreController::class)->name('calendar-feed.store');
    Route::delete('calendar-feed', CalendarFeedDestroyController::class)->name('calendar-feed.destroy');

    // Session attendance register (Part 03 / Ticket 04)
    Route::get('course-sessions/{session}/attendance', AttendanceShowController::class)->name('course-sessions.attendance.show');
    Route::put('course-sessions/{session}/attendance', AttendanceUpdateController::class)->name('course-sessions.attendance.update');

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
