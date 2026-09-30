<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Campus;
use App\Models\Room;
use Illuminate\Database\Seeder;

class ReferentialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Campus Casablanca
        $casa = Campus::firstOrCreate(
            ['code' => 'CASA'],
            [
                'name' => 'Campus Casablanca',
                'address' => '27, Boulevard Bir Anzarane, Maarif',
                'city' => 'Casablanca',
                'is_active' => true,
            ]
        );

        $batA = Building::firstOrCreate(
            ['campus_id' => $casa->id, 'name' => 'Bâtiment A'],
            ['code' => 'BAT-A', 'is_active' => true]
        );

        $batB = Building::firstOrCreate(
            ['campus_id' => $casa->id, 'name' => 'Bâtiment B'],
            ['code' => 'BAT-B', 'is_active' => true]
        );

        $casaRooms = [
            [
                'building_id' => $batA->id,
                'name' => 'Amphi 1',
                'code' => 'A-AMP1',
                'floor' => 0,
                'course_capacity' => 120,
                'exam_capacity' => 60,
                'has_projector' => true,
                'is_lab' => false,
                'has_computers' => false,
                'has_sound_system' => true,
            ],
            [
                'building_id' => $batA->id,
                'name' => 'Salle 101',
                'code' => 'A-101',
                'floor' => 1,
                'course_capacity' => 40,
                'exam_capacity' => 20,
                'has_projector' => true,
                'is_lab' => false,
                'has_computers' => false,
                'has_sound_system' => false,
            ],
            [
                'building_id' => $batA->id,
                'name' => 'Salle 102',
                'code' => 'A-102',
                'floor' => 1,
                'course_capacity' => 35,
                'exam_capacity' => 18,
                'has_projector' => true,
                'is_lab' => false,
                'has_computers' => false,
                'has_sound_system' => false,
            ],
            [
                'building_id' => $batA->id,
                'name' => 'Labo Info 1',
                'code' => 'A-LAB1',
                'floor' => 2,
                'course_capacity' => 30,
                'exam_capacity' => 15,
                'has_projector' => true,
                'is_lab' => true,
                'has_computers' => true,
                'has_sound_system' => false,
            ],
            [
                'building_id' => $batB->id,
                'name' => 'Salle 201',
                'code' => 'B-201',
                'floor' => 2,
                'course_capacity' => 40,
                'exam_capacity' => 20,
                'has_projector' => true,
                'is_lab' => false,
                'has_computers' => false,
                'has_sound_system' => false,
            ],
            [
                'building_id' => $batB->id,
                'name' => 'Salle 202',
                'code' => 'B-202',
                'floor' => 2,
                'course_capacity' => 40,
                'exam_capacity' => 20,
                'has_projector' => false,
                'is_lab' => false,
                'has_computers' => false,
                'has_sound_system' => false,
            ],
            [
                'building_id' => $batB->id,
                'name' => 'Labo Réseaux',
                'code' => 'B-LAB-NET',
                'floor' => 1,
                'course_capacity' => 24,
                'exam_capacity' => 12,
                'has_projector' => true,
                'is_lab' => true,
                'has_computers' => true,
                'has_sound_system' => false,
            ],
        ];

        foreach ($casaRooms as $roomData) {
            Room::firstOrCreate(
                ['building_id' => $roomData['building_id'], 'name' => $roomData['name']],
                $roomData
            );
        }

        // 2. Campus Rabat
        $rabat = Campus::firstOrCreate(
            ['code' => 'RABAT'],
            [
                'name' => 'Campus Rabat',
                'address' => 'Avenue Annakhil, Hay Riad',
                'city' => 'Rabat',
                'is_active' => true,
            ]
        );

        $batCentral = Building::firstOrCreate(
            ['campus_id' => $rabat->id, 'name' => 'Bâtiment Central'],
            ['code' => 'BAT-C', 'is_active' => true]
        );

        $rabatRooms = [
            [
                'building_id' => $batCentral->id,
                'name' => 'Amphi Al-Khawarizmi',
                'code' => 'R-AMP-KH',
                'floor' => 0,
                'course_capacity' => 160,
                'exam_capacity' => 80,
                'has_projector' => true,
                'is_lab' => false,
                'has_computers' => false,
                'has_sound_system' => true,
            ],
            [
                'building_id' => $batCentral->id,
                'name' => 'Salle R1',
                'code' => 'R-101',
                'floor' => 1,
                'course_capacity' => 30,
                'exam_capacity' => 15,
                'has_projector' => true,
                'is_lab' => false,
                'has_computers' => false,
                'has_sound_system' => false,
            ],
            [
                'building_id' => $batCentral->id,
                'name' => 'Salle R2',
                'code' => 'R-102',
                'floor' => 1,
                'course_capacity' => 30,
                'exam_capacity' => 15,
                'has_projector' => true,
                'is_lab' => false,
                'has_computers' => false,
                'has_sound_system' => false,
            ],
        ];

        foreach ($rabatRooms as $roomData) {
            Room::firstOrCreate(
                ['building_id' => $roomData['building_id'], 'name' => $roomData['name']],
                $roomData
            );
        }
    }
}
