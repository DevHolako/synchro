<?php

use App\Http\Controllers\Web\Buildings\BuildingStoreController;
use App\Http\Controllers\Web\Buildings\BuildingToggleActiveController;
use App\Http\Controllers\Web\Buildings\BuildingUpdateController;
use App\Http\Controllers\Web\Campuses\CampusStoreController;
use App\Http\Controllers\Web\Campuses\CampusToggleActiveController;
use App\Http\Controllers\Web\Campuses\CampusUpdateController;
use App\Http\Controllers\Web\Rooms\RoomIndexController;
use App\Http\Controllers\Web\Rooms\RoomStoreController;
use App\Http\Controllers\Web\Rooms\RoomToggleActiveController;
use App\Http\Controllers\Web\Rooms\RoomUpdateController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

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
});

require __DIR__.'/settings.php';
