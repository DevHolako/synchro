<?php

use App\Actions\Imports\FailStaleImportsAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('imports:fail-stale', function (FailStaleImportsAction $action) {
    $this->info($action->execute().' stale import(s) marked as failed.');
})->purpose('Mark spreadsheet imports that will never finish as failed');

// Queue & housekeeping schedule (run by the `scheduler` container: `php artisan schedule:work`).
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('queue:prune-failed', ['--hours' => 168])->daily();
Schedule::command('model:prune')->daily();
Schedule::command('imports:fail-stale')->everyFifteenMinutes()->withoutOverlapping();
