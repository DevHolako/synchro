<?php

namespace App\Actions\Imports\Importers;

use App\Actions\Rooms\CreateRoomAction;
use App\Exceptions\ImportRowException;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Room;
use App\Models\User;
use Closure;

class RoomRowImporter implements RowImporter
{
    private const array TRUE_VALUES = ['1', 'true', 'yes', 'oui', 'y', 'o', 'x'];

    private const array FALSE_VALUES = ['0', 'false', 'no', 'non', 'n'];

    private const array EQUIPMENT_COLUMNS = ['has_projector', 'is_lab', 'has_computers', 'has_sound_system'];

    public function __construct(private readonly CreateRoomAction $createRoom) {}

    public function rules(): array
    {
        $flag = ['nullable', function (string $attribute, mixed $value, Closure $fail): void {
            if (! in_array(mb_strtolower((string) $value), [...self::TRUE_VALUES, ...self::FALSE_VALUES], true)) {
                $fail(__('messages.import_invalid_flag', ['column' => $attribute]));
            }
        }];

        return [
            'campus_code' => ['required', 'string', 'max:20'],
            'building' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'floor' => ['nullable', 'integer', 'between:-5,50'],
            'course_capacity' => ['required', 'integer', 'min:1'],
            'exam_capacity' => ['required', 'integer', 'min:1', 'lte:course_capacity'],
            ...array_fill_keys(self::EQUIPMENT_COLUMNS, $flag),
        ];
    }

    public function uniqueKeys(array $row): array
    {
        return [
            'name' => mb_strtolower(implode('|', [$row['campus_code'] ?? '', $row['building'] ?? '', $row['name'] ?? ''])),
        ];
    }

    public function prepare(array $row): array
    {
        $campus = Campus::query()->where('code', mb_strtoupper((string) $row['campus_code']))->first();

        if ($campus === null) {
            throw new ImportRowException('campus_code', __('messages.import_campus_not_found', ['code' => $row['campus_code']]));
        }

        $building = Building::query()
            ->where('campus_id', $campus->id)
            ->where(fn ($query) => $query->where('name', $row['building'])->orWhere('code', $row['building']))
            ->first();

        if ($building === null) {
            throw new ImportRowException('building', __('messages.import_building_not_found', [
                'building' => $row['building'],
                'campus' => $campus->code,
            ]));
        }

        if (Room::query()->where('building_id', $building->id)->where('name', $row['name'])->exists()) {
            throw new ImportRowException('name', __('messages.import_room_exists', ['name' => $row['name']]));
        }

        return [
            'building_id' => $building->id,
            'name' => $row['name'],
            'code' => $row['code'] ?? null,
            'floor' => isset($row['floor']) ? (int) $row['floor'] : null,
            'course_capacity' => (int) $row['course_capacity'],
            'exam_capacity' => (int) $row['exam_capacity'],
            ...collect(self::EQUIPMENT_COLUMNS)->mapWithKeys(fn (string $column): array => [
                $column => in_array(mb_strtolower((string) ($row[$column] ?? '')), self::TRUE_VALUES, true),
            ])->all(),
        ];
    }

    public function persist(array $payload, User $actor): void
    {
        $this->createRoom->execute($payload);
    }
}
