<?php

use App\Actions\Imports\RunSpreadsheetImportAction;
use App\Enums\ImportStatus;
use App\Jobs\ProcessSpreadsheetImportJob;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Room;
use App\Models\SpreadsheetImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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

test('the imports page lists recent imports with their status', function () {
    $this->withoutVite();
    SpreadsheetImport::factory()->for($this->admin)->create(['status' => ImportStatus::Failed, 'error_count' => 1, 'errors' => [['row' => 2, 'column' => 'name', 'message' => 'x']]]);

    $this->actingAs($this->admin)
        ->get(route('imports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('imports', 1)
            ->where('imports.0.status', 'failed')
            ->where('imports.0.user.name', $this->admin->name)
            ->missing('imports.0.path'));
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
