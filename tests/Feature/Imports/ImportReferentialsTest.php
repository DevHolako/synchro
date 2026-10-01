<?php

use App\Models\Building;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\SpreadsheetImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
    $this->campus = Campus::factory()->create(['code' => 'CASA']);
    $this->building = Building::factory()->for($this->campus)->create(['name' => 'Bloc A', 'code' => 'BA']);
});

function csvUpload(string $name, array $lines, string $delimiter = ','): UploadedFile
{
    $content = implode("\n", array_map(fn (array $line) => implode($delimiter, $line), $lines))."\n";

    return UploadedFile::fake()->createWithContent($name, $content);
}

function importReport(): array
{
    $import = SpreadsheetImport::query()->latest('id')->firstOrFail();

    return [
        'status' => $import->status->value,
        'imported' => $import->imported_count,
        'errors' => $import->errors ?? [],
    ];
}

test('a multi-row room spreadsheet is imported with dual capacities and equipment flags', function () {
    $file = csvUpload('rooms.csv', [
        ['campus_code', 'building', 'name', 'code', 'floor', 'course_capacity', 'exam_capacity', 'has_projector', 'is_lab'],
        ['casa', 'Bloc A', 'Salle A101', 'A101', '1', '40', '20', 'Oui', 'non'],
        ['CASA', 'BA', 'Labo A102', 'A102', '1', '24', '12', '', 'x'],
        [],
        ['CASA', 'Bloc A', 'Amphi A', '', '0', '200', '100', 'yes', '0'],
    ], ';');

    $this->actingAs($this->admin)
        ->post(route('imports.store', 'rooms'), ['file' => $file])
        ->assertRedirect();

    expect(importReport())->toMatchArray(['status' => 'succeeded', 'imported' => 3])
        ->and(Room::where('building_id', $this->building->id)->count())->toBe(3);

    $room = Room::where('name', 'Salle A101')->sole();
    expect($room->course_capacity)->toBe(40)
        ->and($room->exam_capacity)->toBe(20)
        ->and($room->has_projector)->toBeTrue()
        ->and($room->is_lab)->toBeFalse()
        ->and(Room::where('name', 'Labo A102')->sole()->is_lab)->toBeTrue();
});

test('xlsx spreadsheets are imported', function () {
    $path = tempnam(sys_get_temp_dir(), 'rooms').'.xlsx';
    $writer = new XlsxWriter;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues(['campus_code', 'building', 'name', 'course_capacity', 'exam_capacity']));
    $writer->addRow(Row::fromValues(['CASA', 'Bloc A', 'Salle B201', 30, 15]));
    $writer->addRow(Row::fromValues(['CASA', 'Bloc A', 'Salle B202', 32, 16]));
    $writer->close();

    $file = new UploadedFile($path, 'rooms.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', test: true);

    $this->actingAs($this->admin)
        ->post(route('imports.store', 'rooms'), ['file' => $file])
        ->assertSessionHasNoErrors();

    expect(Room::whereIn('name', ['Salle B201', 'Salle B202'])->count())->toBe(2);
});

test('a malformed row rolls back the whole import and reports exact row numbers', function () {
    $file = csvUpload('rooms.csv', [
        ['campus_code', 'building', 'name', 'course_capacity', 'exam_capacity'],
        ['CASA', 'Bloc A', 'Salle Valide', '40', '20'],
        ['CASA', 'Bloc A', 'Salle Invalide', '30', '45'],
        ['NOPE', 'Bloc A', 'Salle Fantome', '30', '10'],
        ['CASA', 'Bloc Z', 'Salle Perdue', '30', '10'],
        ['CASA', 'Bloc A', 'Salle Valide', '40', '20'],
    ]);

    $this->actingAs($this->admin)->post(route('imports.store', 'rooms'), ['file' => $file])->assertRedirect();

    $report = importReport();

    expect($report['status'])->toBe('failed')
        ->and(collect($report['errors'])->map(fn (array $e) => [$e['row'], $e['column']])->all())->toBe([
            [3, 'exam_capacity'],
            [4, 'campus_code'],
            [5, 'building'],
            [6, 'name'],
        ])
        ->and(Room::count())->toBe(0);
});

test('rows that conflict while saving roll back rows already written', function () {
    // The same building referenced by name then by code passes the in-file check but collides on insert.
    $file = csvUpload('rooms.csv', [
        ['campus_code', 'building', 'name', 'course_capacity', 'exam_capacity'],
        ['CASA', 'Bloc A', 'Salle Doublon', '40', '20'],
        ['CASA', 'BA', 'Salle Doublon', '40', '20'],
    ]);

    $this->actingAs($this->admin)->post(route('imports.store', 'rooms'), ['file' => $file])->assertRedirect();

    expect(importReport()['errors'][0]['row'])->toBe(3)
        ->and(Room::count())->toBe(0);
});

test('missing required columns are reported against the header row', function () {
    $file = csvUpload('rooms.csv', [
        ['campus_code', 'name', 'course_capacity'],
        ['CASA', 'Salle', '40'],
    ]);

    $this->actingAs($this->admin)->post(route('imports.store', 'rooms'), ['file' => $file]);

    $error = importReport()['errors'][0];

    expect($error['row'])->toBe(1)
        ->and($error['message'])->toContain('building')->toContain('exam_capacity');
});

test('a module spreadsheet resolves programs and assigned teachers', function () {
    $department = Department::factory()->create(['code' => 'ISI']);
    $program = Program::factory()->for($department)->create(['code' => '1CI']);
    $teacher = User::factory()->teacher()->create(['email' => 'prof@isga.ma']);

    $file = csvUpload('modules.csv', [
        ['department_code', 'program_code', 'code', 'name', 'total_hours', 'lecture_hours', 'tp_hours', 'teacher_email'],
        ['ISI', '1CI', 'algo-101', 'Algorithmique', '40', '24', '16', 'PROF@isga.ma'],
        ['ISI', '1CI', 'BDD-101', 'Bases de données', '30', '20', '10', ''],
    ]);

    $this->actingAs($this->admin)->post(route('imports.store', 'modules'), ['file' => $file])->assertRedirect();

    $module = Module::where('code', 'ALGO-101')->sole();

    expect(Module::where('program_id', $program->id)->count())->toBe(2)
        ->and($module->teacher_id)->toBe($teacher->id)
        ->and($module->color_code)->toBe('#3B82F6');
});

test('module syllabus hours are validated per row', function () {
    $department = Department::factory()->create(['code' => 'ISI']);
    Program::factory()->for($department)->create(['code' => '1CI']);

    $file = csvUpload('modules.csv', [
        ['department_code', 'program_code', 'code', 'name', 'total_hours', 'lecture_hours', 'tp_hours'],
        ['ISI', '1CI', 'OK-1', 'Valide', '40', '20', '20'],
        ['ISI', '1CI', 'KO-1', 'Trop d heures', '40', '30', '20'],
        ['ISI', '9CI', 'KO-2', 'Filière inconnue', '40', '20', '20'],
    ]);

    $this->actingAs($this->admin)->post(route('imports.store', 'modules'), ['file' => $file]);

    expect(collect(importReport()['errors'])->map(fn (array $e) => [$e['row'], $e['column']])->all())
        ->toBe([[3, 'lecture_hours'], [4, 'program_code']])
        ->and(Module::count())->toBe(0);
});
