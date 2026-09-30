<?php

namespace Database\Seeders;

use App\Enums\ProgramModality;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Program;
use App\Models\StudentGroup;
use Illuminate\Database\Seeder;

class AcademicStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $casaCampus = Campus::where('code', 'CASA')->first();
        $rabatCampus = Campus::where('code', 'RABAT')->first();

        // 1. Department ISI
        $isi = Department::firstOrCreate(
            ['code' => 'ISI'],
            [
                'name' => 'Informatique & Systèmes d\'Information',
                'description' => 'Filières d\'ingénierie logicielle, réseaux, cybersécurité et intelligence artificielle.',
                'is_active' => true,
            ]
        );

        // Programs under ISI
        $prog1ci = Program::firstOrCreate(
            ['department_id' => $isi->id, 'code' => '1CI'],
            [
                'name' => '1ère Année Cycle Ingénieur (1CI)',
                'program_modality' => ProgramModality::FormationInitiale,
                'description' => 'Tronc commun d\'ingénierie informatique en formation initiale.',
                'is_active' => true,
            ]
        );

        $prog2ci = Program::firstOrCreate(
            ['department_id' => $isi->id, 'code' => '2CI'],
            [
                'name' => '2ème Année Cycle Ingénieur (2CI)',
                'program_modality' => ProgramModality::FormationInitiale,
                'description' => 'Spécialisation ingénierie logicielle et data en formation initiale.',
                'is_active' => true,
            ]
        );

        $progMiage = Program::firstOrCreate(
            ['department_id' => $isi->id, 'code' => 'M-MIAGE-TA'],
            [
                'name' => 'Master MIAGE (Temps Aménagé)',
                'program_modality' => ProgramModality::TempsAmenage,
                'description' => 'Méthodes informatiques appliquées à la gestion des entreprises en cours du soir et weekend.',
                'is_active' => true,
            ]
        );

        $progEmba = Program::firstOrCreate(
            ['department_id' => $isi->id, 'code' => 'EMBA-IA'],
            [
                'name' => 'Executive MBA Big Data & IA (Temps Aménagé)',
                'program_modality' => ProgramModality::TempsAmenage,
                'description' => 'Programme executive spécialisé en data science et intelligence artificielle.',
                'is_active' => true,
            ]
        );

        // Groups for 1CI
        StudentGroup::firstOrCreate(
            ['program_id' => $prog1ci->id, 'academic_year' => '2026-2027', 'name' => '1CI - G1'],
            [
                'code' => '1CI-G1',
                'campus_id' => $casaCampus?->id,
                'expected_headcount' => 32,
                'is_active' => true,
            ]
        );

        StudentGroup::firstOrCreate(
            ['program_id' => $prog1ci->id, 'academic_year' => '2026-2027', 'name' => '1CI - G2'],
            [
                'code' => '1CI-G2',
                'campus_id' => $casaCampus?->id,
                'expected_headcount' => 28,
                'is_active' => true,
            ]
        );

        StudentGroup::firstOrCreate(
            ['program_id' => $prog1ci->id, 'academic_year' => '2026-2027', 'name' => '1CI - Rabat'],
            [
                'code' => '1CI-RAB',
                'campus_id' => $rabatCampus?->id,
                'expected_headcount' => 25,
                'is_active' => true,
            ]
        );

        // Groups for 2CI
        StudentGroup::firstOrCreate(
            ['program_id' => $prog2ci->id, 'academic_year' => '2026-2027', 'name' => '2CI - G1'],
            [
                'code' => '2CI-G1',
                'campus_id' => $casaCampus?->id,
                'expected_headcount' => 30,
                'is_active' => true,
            ]
        );

        // Groups for MIAGE TA
        StudentGroup::firstOrCreate(
            ['program_id' => $progMiage->id, 'academic_year' => '2026-2027', 'name' => 'MIAGE TA - Promo 2026'],
            [
                'code' => 'MIAGE-TA-26',
                'campus_id' => $casaCampus?->id,
                'expected_headcount' => 24,
                'is_active' => true,
            ]
        );

        // Groups for EMBA IA
        StudentGroup::firstOrCreate(
            ['program_id' => $progEmba->id, 'academic_year' => '2026-2027', 'name' => 'EMBA IA - Weekend'],
            [
                'code' => 'EMBA-IA-WKD',
                'campus_id' => $casaCampus?->id,
                'expected_headcount' => 18,
                'is_active' => true,
            ]
        );

        // 2. Department Management
        $mgt = Department::firstOrCreate(
            ['code' => 'MGT'],
            [
                'name' => 'Management & Commerce',
                'description' => 'Filières de gestion, finance d\'entreprise et marketing digital.',
                'is_active' => true,
            ]
        );

        $progLsg = Program::firstOrCreate(
            ['department_id' => $mgt->id, 'code' => 'LSG'],
            [
                'name' => 'Licence Sciences de Gestion',
                'program_modality' => ProgramModality::FormationInitiale,
                'description' => 'Programme de premier cycle en management.',
                'is_active' => true,
            ]
        );

        StudentGroup::firstOrCreate(
            ['program_id' => $progLsg->id, 'academic_year' => '2026-2027', 'name' => 'LSG - G1'],
            [
                'code' => 'LSG-G1',
                'campus_id' => $casaCampus?->id,
                'expected_headcount' => 35,
                'is_active' => true,
            ]
        );
    }
}
