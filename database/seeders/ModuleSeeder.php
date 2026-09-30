<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Module;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Teachers
        $teacher1 = User::firstOrCreate(
            ['email' => 'teacher1@synchro.isga.ma'],
            [
                'name' => 'Pr. Karim Amrani',
                'password' => 'password',
                'role' => UserRole::Teacher,
                'email_verified_at' => now(),
            ]
        );

        $teacher2 = User::firstOrCreate(
            ['email' => 'teacher2@synchro.isga.ma'],
            [
                'name' => 'Pr. Fatima Zahra Bennani',
                'password' => 'password',
                'role' => UserRole::Teacher,
                'email_verified_at' => now(),
            ]
        );

        $teacher3 = User::firstOrCreate(
            ['email' => 'teacher3@synchro.isga.ma'],
            [
                'name' => 'Pr. Youssef El Mansouri',
                'password' => 'password',
                'role' => UserRole::Teacher,
                'email_verified_at' => now(),
            ]
        );

        // 2. Programs
        $prog1ci = Program::where('code', '1CI')->first();
        $prog2ci = Program::where('code', '2CI')->first();
        $progMiage = Program::where('code', 'M-MIAGE-TA')->first();

        if ($prog1ci) {
            Module::firstOrCreate(
                ['program_id' => $prog1ci->id, 'code' => 'ISI-101'],
                [
                    'teacher_id' => $teacher1->id,
                    'name' => 'Algorithmique & Structures de Données',
                    'total_hours' => 45,
                    'lecture_hours' => 30,
                    'tp_hours' => 15,
                    'color_code' => '#3B82F6',
                    'description' => 'Complexité algorithmique, listes, arbres et graphes.',
                    'is_active' => true,
                ]
            );

            Module::firstOrCreate(
                ['program_id' => $prog1ci->id, 'code' => 'ISI-102'],
                [
                    'teacher_id' => $teacher2->id,
                    'name' => 'Bases de Données Relationnelles & SQL',
                    'total_hours' => 40,
                    'lecture_hours' => 24,
                    'tp_hours' => 16,
                    'color_code' => '#10B981',
                    'description' => 'Modélisation entité-association, algèbre relationnelle et optimisation SQL.',
                    'is_active' => true,
                ]
            );

            Module::firstOrCreate(
                ['program_id' => $prog1ci->id, 'code' => 'ISI-103'],
                [
                    'teacher_id' => $teacher3->id,
                    'name' => 'Architecture des Ordinateurs & Réseaux',
                    'total_hours' => 35,
                    'lecture_hours' => 20,
                    'tp_hours' => 15,
                    'color_code' => '#8B5CF6',
                    'description' => 'Couches OSI, adressage IP, routage et virtualisation réseau.',
                    'is_active' => true,
                ]
            );
        }

        if ($prog2ci) {
            Module::firstOrCreate(
                ['program_id' => $prog2ci->id, 'code' => 'ISI-201'],
                [
                    'teacher_id' => $teacher1->id,
                    'name' => 'Développement Fullstack & API REST',
                    'total_hours' => 45,
                    'lecture_hours' => 25,
                    'tp_hours' => 20,
                    'color_code' => '#EC4899',
                    'description' => 'Architecture MVC moderne, SPA React et backends Laravel.',
                    'is_active' => true,
                ]
            );

            Module::firstOrCreate(
                ['program_id' => $prog2ci->id, 'code' => 'ISI-202'],
                [
                    'teacher_id' => $teacher2->id,
                    'name' => 'Big Data & Traitement Distribué',
                    'total_hours' => 40,
                    'lecture_hours' => 20,
                    'tp_hours' => 20,
                    'color_code' => '#F59E0B',
                    'description' => 'Ecosystème Hadoop, Apache Spark, NoSQL et data pipelines.',
                    'is_active' => true,
                ]
            );
        }

        if ($progMiage) {
            Module::firstOrCreate(
                ['program_id' => $progMiage->id, 'code' => 'MIA-TA-01'],
                [
                    'teacher_id' => $teacher3->id,
                    'name' => 'Gouvernance SI & Méthodes Agiles',
                    'total_hours' => 30,
                    'lecture_hours' => 20,
                    'tp_hours' => 10,
                    'color_code' => '#6366F1',
                    'description' => 'Cadres Scrum, ITIL, gestion de portefeuille et audit des SI.',
                    'is_active' => true,
                ]
            );
        }
    }
}
