<?php

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Models\SpreadsheetImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpreadsheetImport>
 */
class SpreadsheetImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'type' => ImportType::Rooms,
            'status' => ImportStatus::Pending,
            'original_filename' => 'rooms.csv',
            'disk' => 'local',
            'path' => 'imports/'.fake()->uuid().'.csv',
            'extension' => 'csv',
        ];
    }
}
