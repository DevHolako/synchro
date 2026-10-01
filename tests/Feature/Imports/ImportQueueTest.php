<?php

use App\Actions\Imports\FailStaleImportsAction;
use App\Actions\Imports\ImportReferentialsAction;
use App\Actions\Imports\RunSpreadsheetImportAction;
use App\Enums\ImportStatus;
use App\Jobs\ProcessSpreadsheetImportJob;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Room;
use App\Models\SpreadsheetImport;
use App\Models\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
    $campus = Campus::factory()->create(['code' => 'CASA']);
    Building::factory()->for($campus)->create(['name' => 'Bloc A']);
});

function roomsUpload(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('rooms.csv', implode("\n", [
        'campus_code,building,name,course_capacity,exam_capacity',
        'CASA,Bloc A,Salle Q1,40,20',
        'CASA,Bloc A,Salle Q2,30,15',
    ])."\n");
}

test('uploading a spreadsheet stores it and dispatches an import job on the imports queue', function () {
    Queue::fake();

    $this->actingAs($this->admin)
        ->post(route('imports.store', 'rooms'), ['file' => roomsUpload()])
        ->assertRedirect();

    $import = SpreadsheetImport::sole();

    expect($import->status)->toBe(ImportStatus::Pending)
        ->and($import->user_id)->toBe($this->admin->id)
        ->and($import->original_filename)->toBe('rooms.csv')
        ->and(Room::count())->toBe(0);

    Storage::disk('local')->assertExists($import->path);
    Queue::assertPushedOn('imports', ProcessSpreadsheetImportJob::class, fn ($job) => $job->import->is($import));
});

test('the import job records success and removes the uploaded file', function () {
    Queue::fake();
    $this->actingAs($this->admin)->post(route('imports.store', 'rooms'), ['file' => roomsUpload()]);
    $import = SpreadsheetImport::sole();
    $path = $import->path;

    (new ProcessSpreadsheetImportJob($import))->handle(app(RunSpreadsheetImportAction::class));

    $import->refresh();

    expect($import->status)->toBe(ImportStatus::Succeeded)
        ->and($import->imported_count)->toBe(2)
        ->and($import->started_at)->not->toBeNull()
        ->and($import->finished_at)->not->toBeNull()
        ->and($import->path)->toBeNull()
        ->and(Room::count())->toBe(2);

    Storage::disk('local')->assertMissing($path);
});

test('a redelivered import job does not import the file twice', function () {
    Queue::fake();
    $this->actingAs($this->admin)->post(route('imports.store', 'rooms'), ['file' => roomsUpload()]);
    $import = SpreadsheetImport::sole();
    $action = app(RunSpreadsheetImportAction::class);

    $action->execute($import);
    $action->execute($import);

    expect(Room::count())->toBe(2)
        ->and($import->refresh()->imported_count)->toBe(2);
});

test('a crashed import job is recorded as failed', function () {
    Queue::fake();
    $this->actingAs($this->admin)->post(route('imports.store', 'rooms'), ['file' => roomsUpload()]);
    $import = SpreadsheetImport::sole();

    (new ProcessSpreadsheetImportJob($import))->failed(new RuntimeException('worker died'));

    $import->refresh();

    expect($import->status)->toBe(ImportStatus::Failed)
        ->and($import->errors[0]['message'])->toBe(__('messages.import_crashed'))
        ->and($import->path)->toBeNull()
        ->and(Room::count())->toBe(0);
});

test('a job timing out mid-transaction rolls the import back and records the failure', function () {
    Storage::disk('local')->put($path = 'imports/rooms.csv', 'x');
    $import = SpreadsheetImport::factory()->for($this->admin)->create(['path' => $path]);
    $baseLevel = DB::transactionLevel();

    $this->mock(ImportReferentialsAction::class)->shouldReceive('execute')->andReturnUsing(function () use ($import) {
        DB::beginTransaction();
        Room::factory()->create();

        // What the worker's SIGALRM handler does before killing the process.
        (new ProcessSpreadsheetImportJob($import))->failed(new RuntimeException('timed out'));

        throw new RuntimeException('worker killed');
    });

    expect(fn () => app(RunSpreadsheetImportAction::class)->execute($import))->toThrow(RuntimeException::class, 'worker killed');

    $import->refresh();

    expect(DB::transactionLevel())->toBe($baseLevel)
        ->and(Room::count())->toBe(0)
        ->and($import->status)->toBe(ImportStatus::Failed)
        ->and($import->errors[0]['message'])->toBe(__('messages.import_crashed'))
        ->and($import->finished_at)->not->toBeNull()
        ->and($import->path)->toBeNull();

    Storage::disk('local')->assertMissing($path);
});

test('an upload the queue cannot accept is recorded as failed instead of left pending', function () {
    Exceptions::fake();
    $this->mock(Dispatcher::class)->shouldReceive('dispatch')->andThrow(new RuntimeException('redis down'));

    $this->actingAs($this->admin)
        ->post(route('imports.store', 'rooms'), ['file' => roomsUpload()])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'error');

    $import = SpreadsheetImport::sole();

    expect($import->status)->toBe(ImportStatus::Failed)
        ->and($import->errors[0]['message'])->toBe(__('messages.import_queue_unavailable'))
        ->and($import->path)->toBeNull()
        ->and(Storage::disk('local')->allFiles('imports'))->toBe([]);

    Exceptions::assertReported(fn (RuntimeException $exception) => $exception->getMessage() === 'redis down');
});

test('imports that will never finish are marked as failed', function () {
    Storage::disk('local')->put('imports/stale.csv', 'x');
    $stalePending = SpreadsheetImport::factory()->create(['path' => 'imports/stale.csv', 'created_at' => now()->subHours(FailStaleImportsAction::PENDING_HOURS + 1)]);
    $staleProcessing = SpreadsheetImport::factory()->create(['status' => ImportStatus::Processing, 'started_at' => now()->subHour()]);
    $freshPending = SpreadsheetImport::factory()->create();
    $runningProcessing = SpreadsheetImport::factory()->create(['status' => ImportStatus::Processing, 'started_at' => now()->subMinutes(5)]);

    $this->artisan('imports:fail-stale')->assertSuccessful();

    expect($stalePending->refresh()->status)->toBe(ImportStatus::Failed)
        ->and($stalePending->errors[0]['message'])->toBe(__('messages.import_stale'))
        ->and($stalePending->path)->toBeNull()
        ->and($staleProcessing->refresh()->status)->toBe(ImportStatus::Failed)
        ->and($freshPending->refresh()->status)->toBe(ImportStatus::Pending)
        ->and($runningProcessing->refresh()->status)->toBe(ImportStatus::Processing);

    Storage::disk('local')->assertMissing('imports/stale.csv');

    // A late delivery of the abandoned job must not import the file.
    expect(app(RunSpreadsheetImportAction::class)->execute($stalePending)->status)->toBe(ImportStatus::Failed);
});

test('the imports page lists recent imports with their status but without error reports', function () {
    $this->withoutVite();
    SpreadsheetImport::factory()->for($this->admin)->create(['status' => ImportStatus::Failed, 'error_count' => 1, 'errors' => [['row' => 2, 'column' => 'name', 'message' => 'x']]]);

    $this->actingAs($this->admin)
        ->get(route('imports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('imports', 1)
            ->where('imports.0.status', 'failed')
            ->where('imports.0.user_id', $this->admin->id)
            ->where('imports.0.user.name', $this->admin->name)
            ->missing('imports.0.errors')
            ->missing('imports.0.path')
            ->missing('report'));
});

test('a failed import report is loaded on request', function () {
    $this->withoutVite();
    $failed = SpreadsheetImport::factory()->for($this->admin)->create(['status' => ImportStatus::Failed, 'error_count' => 1, 'errors' => [['row' => 2, 'column' => 'name', 'message' => 'x']]]);
    $succeeded = SpreadsheetImport::factory()->for($this->admin)->create(['status' => ImportStatus::Succeeded]);

    $this->actingAs($this->admin)
        ->get(route('imports.index', ['report' => $failed->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->reloadOnly('report', fn (Assert $reload) => $reload
                ->where('report.id', $failed->id)
                ->where('report.errors.0.message', 'x')));

    $this->actingAs($this->admin)
        ->get(route('imports.index', ['report' => $succeeded->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->reloadOnly('report', fn (Assert $reload) => $reload->where('report', null)));
});

test('finished imports past the retention window are pruned', function () {
    $old = SpreadsheetImport::factory()->create(['status' => ImportStatus::Succeeded, 'created_at' => now()->subDays(SpreadsheetImport::RETENTION_DAYS + 1)]);
    $recent = SpreadsheetImport::factory()->create(['status' => ImportStatus::Succeeded]);
    $stuck = SpreadsheetImport::factory()->create(['status' => ImportStatus::Pending, 'created_at' => now()->subDays(SpreadsheetImport::RETENTION_DAYS + 1)]);

    $this->artisan('model:prune', ['--model' => [SpreadsheetImport::class]])->assertSuccessful();

    expect(SpreadsheetImport::find($old->id))->toBeNull()
        ->and(SpreadsheetImport::find($recent->id))->not->toBeNull()
        ->and(SpreadsheetImport::find($stuck->id))->not->toBeNull();
});
