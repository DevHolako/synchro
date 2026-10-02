<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\AttendanceStatus;
use App\Enums\ConflictType;
use App\Enums\ExamSessionType;
use App\Enums\ExamState;
use App\Enums\GradeSheetStatus;
use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Enums\InvigilatorRole;
use App\Enums\ProgramModality;
use App\Enums\UnavailabilityStatus;
use App\Enums\UnavailabilityType;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Campus;
use App\Models\ConflictOverride;
use App\Models\CourseSession;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Models\ExamDeliberation;
use App\Models\ExamGrade;
use App\Models\ExamInvigilator;
use App\Models\ExamPeriod;
use App\Models\ExamRoomAssignment;
use App\Models\InvitationToken;
use App\Models\Module;
use App\Models\Program;
use App\Models\Room;
use App\Models\SessionAttendance;
use App\Models\SpreadsheetImport;
use App\Models\StudentGroup;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\TeacherUnavailability;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FakeDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private string $password;

    /** @var array<string, Campus> */
    private array $campuses = [];

    /** @var list<Room> */
    private array $rooms = [];

    /** @var array<string, Department> */
    private array $departments = [];

    /** @var list<Program> */
    private array $programs = [];

    /** @var list<StudentGroup> */
    private array $studentGroups = [];

    /** @var list<User> */
    private array $coordinators = [];

    /** @var list<User> */
    private array $teachers = [];

    /** @var list<User> */
    private array $students = [];

    /** @var list<Module> */
    private array $modules = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->password = Hash::make('password');

        $this->command?->info('🌱 Starting massive fake data seeding...');

        DB::transaction(function (): void {
            $this->seedCampusesAndRooms();
            $this->seedDepartmentsProgramsAndGroups();
            $this->seedStaffAndTeachers();
            $this->seedStudents();
            $this->seedModules();
            $this->seedTeacherUnavailabilities();
            $this->seedCourseSessionsAndAttendance();
            $this->seedExamPeriodsAndExams();
            $this->seedSpreadsheetImports();
        });

        $this->command?->info('✨ Massive fake data seeding completed successfully!');
    }

    /**
     * Seed 5 campuses, multiple buildings, and 45+ rooms matching schema unique constraints.
     */
    private function seedCampusesAndRooms(): void
    {
        $this->command?->line('  → Seeding campuses, buildings, and rooms...');

        $campusData = [
            'CASA' => [
                'name' => 'Campus Casablanca',
                'address' => '27, Boulevard Bir Anzarane, Maarif',
                'city' => 'Casablanca',
                'buildings' => [
                    'BAT-A' => [
                        'name' => 'Bâtiment A',
                        'rooms' => [
                            ['name' => 'Amphi 1', 'code' => 'A-AMP1', 'floor' => 0, 'course_capacity' => 120, 'exam_capacity' => 60, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => true],
                            ['name' => 'Amphi Al Khawarizmi', 'code' => 'A-AMP-KH', 'floor' => 0, 'course_capacity' => 140, 'exam_capacity' => 70, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => true],
                            ['name' => 'Salle 101', 'code' => 'A-101', 'floor' => 1, 'course_capacity' => 40, 'exam_capacity' => 20, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Salle 102', 'code' => 'A-102', 'floor' => 1, 'course_capacity' => 35, 'exam_capacity' => 18, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Salle 103', 'code' => 'A-103', 'floor' => 1, 'course_capacity' => 45, 'exam_capacity' => 22, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Labo Info 1', 'code' => 'A-LAB1', 'floor' => 2, 'course_capacity' => 30, 'exam_capacity' => 15, 'has_projector' => true, 'is_lab' => true, 'has_computers' => true, 'has_sound_system' => false],
                            ['name' => 'Labo Info 2', 'code' => 'A-LAB2', 'floor' => 2, 'course_capacity' => 32, 'exam_capacity' => 16, 'has_projector' => true, 'is_lab' => true, 'has_computers' => true, 'has_sound_system' => false],
                            ['name' => 'Labo IA & Data', 'code' => 'A-LAB-IA', 'floor' => 2, 'course_capacity' => 28, 'exam_capacity' => 14, 'has_projector' => true, 'is_lab' => true, 'has_computers' => true, 'has_sound_system' => false],
                        ],
                    ],
                    'BAT-B' => [
                        'name' => 'Bâtiment B',
                        'rooms' => [
                            ['name' => 'Salle 201', 'code' => 'B-201', 'floor' => 2, 'course_capacity' => 40, 'exam_capacity' => 20, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Salle 202', 'code' => 'B-202', 'floor' => 2, 'course_capacity' => 40, 'exam_capacity' => 20, 'has_projector' => false, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Salle 203', 'code' => 'B-203', 'floor' => 1, 'course_capacity' => 35, 'exam_capacity' => 18, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Labo Réseaux', 'code' => 'B-LAB-NET', 'floor' => 1, 'course_capacity' => 24, 'exam_capacity' => 12, 'has_projector' => true, 'is_lab' => true, 'has_computers' => true, 'has_sound_system' => false],
                            ['name' => 'Salle de Séminaire B', 'code' => 'B-SEM', 'floor' => 2, 'course_capacity' => 50, 'exam_capacity' => 25, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => true],
                        ],
                    ],
                    'BAT-INNOV' => [
                        'name' => 'Centre d\'Innovation',
                        'rooms' => [
                            ['name' => 'Amphi Innovation', 'code' => 'INN-AMP', 'floor' => 0, 'course_capacity' => 120, 'exam_capacity' => 60, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => true],
                            ['name' => 'Labo IoT & Robotique', 'code' => 'INN-IOT', 'floor' => 1, 'course_capacity' => 25, 'exam_capacity' => 12, 'has_projector' => true, 'is_lab' => true, 'has_computers' => true, 'has_sound_system' => false],
                            ['name' => 'Espace Coworking', 'code' => 'INN-COW', 'floor' => 1, 'course_capacity' => 50, 'exam_capacity' => 25, 'has_projector' => true, 'is_lab' => false, 'has_computers' => true, 'has_sound_system' => false],
                        ],
                    ],
                ],
            ],
            'RABAT' => [
                'name' => 'Campus Rabat',
                'address' => 'Avenue Annakhil, Hay Riad',
                'city' => 'Rabat',
                'buildings' => [
                    'BAT-C' => [
                        'name' => 'Bâtiment Central',
                        'rooms' => [
                            ['name' => 'Amphi Al-Khawarizmi', 'code' => 'R-AMP-KH', 'floor' => 0, 'course_capacity' => 160, 'exam_capacity' => 80, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => true],
                            ['name' => 'Salle R1', 'code' => 'R-1', 'floor' => 1, 'course_capacity' => 45, 'exam_capacity' => 22, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Salle R2', 'code' => 'R-2', 'floor' => 1, 'course_capacity' => 40, 'exam_capacity' => 20, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Salle R3', 'code' => 'R-3', 'floor' => 2, 'course_capacity' => 35, 'exam_capacity' => 18, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Labo Info Rabat', 'code' => 'R-LAB1', 'floor' => 2, 'course_capacity' => 32, 'exam_capacity' => 16, 'has_projector' => true, 'is_lab' => true, 'has_computers' => true, 'has_sound_system' => false],
                            ['name' => 'Labo Cybersécurité Rabat', 'code' => 'R-LAB-CYB', 'floor' => 2, 'course_capacity' => 28, 'exam_capacity' => 14, 'has_projector' => true, 'is_lab' => true, 'has_computers' => true, 'has_sound_system' => false],
                        ],
                    ],
                ],
            ],
            'MARRAKECH' => [
                'name' => 'Campus Marrakech Guéliz',
                'address' => 'Boulevard Abdelkrim Al Khattabi',
                'city' => 'Marrakech',
                'buildings' => [
                    'BAT-M1' => [
                        'name' => 'Bâtiment Majorelle',
                        'rooms' => [
                            ['name' => 'Amphi Menara', 'code' => 'M-AMP-MEN', 'floor' => 0, 'course_capacity' => 110, 'exam_capacity' => 55, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => true],
                            ['name' => 'Salle M101', 'code' => 'M-101', 'floor' => 1, 'course_capacity' => 40, 'exam_capacity' => 20, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Salle M102', 'code' => 'M-102', 'floor' => 1, 'course_capacity' => 35, 'exam_capacity' => 18, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Labo Info Marrakech', 'code' => 'M-LAB-1', 'floor' => 2, 'course_capacity' => 30, 'exam_capacity' => 15, 'has_projector' => true, 'is_lab' => true, 'has_computers' => true, 'has_sound_system' => false],
                        ],
                    ],
                ],
            ],
            'TANGER' => [
                'name' => 'Campus Tanger Malabata',
                'address' => 'Route de Malabata, Baie de Tanger',
                'city' => 'Tanger',
                'buildings' => [
                    'BAT-T1' => [
                        'name' => 'Bâtiment Détroit',
                        'rooms' => [
                            ['name' => 'Amphi Ibn Battouta', 'code' => 'T-AMP-IBN', 'floor' => 0, 'course_capacity' => 120, 'exam_capacity' => 60, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => true],
                            ['name' => 'Salle T101', 'code' => 'T-101', 'floor' => 1, 'course_capacity' => 40, 'exam_capacity' => 20, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Labo Smart Logistics', 'code' => 'T-LAB-LOG', 'floor' => 2, 'course_capacity' => 28, 'exam_capacity' => 14, 'has_projector' => true, 'is_lab' => true, 'has_computers' => true, 'has_sound_system' => false],
                        ],
                    ],
                ],
            ],
            'FES' => [
                'name' => 'Campus Fès Saïss',
                'address' => 'Avenue Allal Ben Abdellah',
                'city' => 'Fès',
                'buildings' => [
                    'BAT-F1' => [
                        'name' => 'Bâtiment Al Qaraouiyine',
                        'rooms' => [
                            ['name' => 'Amphi Fès', 'code' => 'F-AMP-FES', 'floor' => 0, 'course_capacity' => 100, 'exam_capacity' => 50, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => true],
                            ['name' => 'Salle F101', 'code' => 'F-101', 'floor' => 1, 'course_capacity' => 38, 'exam_capacity' => 19, 'has_projector' => true, 'is_lab' => false, 'has_computers' => false, 'has_sound_system' => false],
                            ['name' => 'Labo Systèmes Embarqués', 'code' => 'F-LAB-EMB', 'floor' => 2, 'course_capacity' => 26, 'exam_capacity' => 13, 'has_projector' => true, 'is_lab' => true, 'has_computers' => true, 'has_sound_system' => false],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($campusData as $code => $data) {
            $campus = Campus::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'is_active' => true,
                ]
            );
            $this->campuses[$code] = $campus;

            foreach ($data['buildings'] as $bCode => $bData) {
                // Match by unique constraint: ['campus_id', 'name']
                $building = Building::firstOrCreate(
                    ['campus_id' => $campus->id, 'name' => $bData['name']],
                    [
                        'code' => $bCode,
                        'is_active' => true,
                    ]
                );

                foreach ($bData['rooms'] as $roomSpec) {
                    // Match by unique constraint: ['building_id', 'name']
                    $room = Room::firstOrCreate(
                        ['building_id' => $building->id, 'name' => $roomSpec['name']],
                        [
                            'code' => $roomSpec['code'],
                            'floor' => $roomSpec['floor'],
                            'course_capacity' => $roomSpec['course_capacity'],
                            'exam_capacity' => $roomSpec['exam_capacity'],
                            'has_projector' => $roomSpec['has_projector'],
                            'is_lab' => $roomSpec['is_lab'],
                            'has_computers' => $roomSpec['has_computers'],
                            'has_sound_system' => $roomSpec['has_sound_system'],
                            'is_active' => true,
                        ]
                    );
                    $this->rooms[] = $room;
                }
            }
        }
    }

    /**
     * Seed departments, programs, and student groups.
     */
    private function seedDepartmentsProgramsAndGroups(): void
    {
        $this->command?->line('  → Seeding departments, programs, and student groups...');

        $departmentSpecs = [
            'ISI' => [
                'name' => 'Informatique & Systèmes d\'Information',
                'description' => 'Filières d\'ingénierie logicielle, réseaux, cybersécurité et intelligence artificielle.',
                'programs' => [
                    [
                        'code' => '1CI',
                        'name' => '1ère Année Cycle Ingénieur (1CI)',
                        'modality' => ProgramModality::FormationInitiale,
                        'groups' => [
                            ['name' => '1CI - G1', 'code' => '1CI-G1', 'campus' => 'CASA', 'count' => 32],
                            ['name' => '1CI - G2', 'code' => '1CI-G2', 'campus' => 'CASA', 'count' => 28],
                            ['name' => '1CI - Groupe Rabat', 'code' => '1CI-RAB', 'campus' => 'RABAT', 'count' => 26],
                            ['name' => '1CI - Groupe Marrakech', 'code' => '1CI-MRK', 'campus' => 'MARRAKECH', 'count' => 22],
                        ],
                    ],
                    [
                        'code' => '2CI',
                        'name' => '2ème Année Cycle Ingénieur (2CI)',
                        'modality' => ProgramModality::FormationInitiale,
                        'groups' => [
                            ['name' => '2CI - Groupe 1', 'code' => '2CI-G1', 'campus' => 'CASA', 'count' => 25],
                            ['name' => '2CI - Groupe Rabat', 'code' => '2CI-RAB', 'campus' => 'RABAT', 'count' => 22],
                        ],
                    ],
                    [
                        'code' => '3CI-CLOUD',
                        'name' => '3ème Année Cloud Architecture & DevOps (3CI-CLOUD)',
                        'modality' => ProgramModality::FormationInitiale,
                        'groups' => [
                            ['name' => '3CI Cloud & DevOps', 'code' => '3CI-CLD', 'campus' => 'CASA', 'count' => 20],
                        ],
                    ],
                    [
                        'code' => 'M-MIAGE-TA',
                        'name' => 'Master MIAGE (Temps Aménagé)',
                        'modality' => ProgramModality::TempsAmenage,
                        'groups' => [
                            ['name' => 'Master MIAGE TA - Casa', 'code' => 'M-MIAGE-C', 'campus' => 'CASA', 'count' => 24],
                            ['name' => 'Master MIAGE TA - Rabat', 'code' => 'M-MIAGE-R', 'campus' => 'RABAT', 'count' => 20],
                        ],
                    ],
                ],
            ],
            'DIA' => [
                'name' => 'Data Science & Intelligence Artificielle',
                'description' => 'Machine learning, deep learning, big data analytics et vision par ordinateur.',
                'programs' => [
                    [
                        'code' => '2CI-DATA',
                        'name' => '2ème Année Data Science & IA (2CI-DATA)',
                        'modality' => ProgramModality::FormationInitiale,
                        'groups' => [
                            ['name' => '2CI Data Science - Casa', 'code' => '2CI-DATA1', 'campus' => 'CASA', 'count' => 22],
                        ],
                    ],
                    [
                        'code' => 'EMBA-IA',
                        'name' => 'Executive MBA Big Data & IA (Temps Aménagé)',
                        'modality' => ProgramModality::TempsAmenage,
                        'groups' => [
                            ['name' => 'EMBA Data & IA', 'code' => 'EMBA-IA-1', 'campus' => 'CASA', 'count' => 18],
                        ],
                    ],
                    [
                        'code' => 'LP-DATA',
                        'name' => 'Licence Pro Data Analytics',
                        'modality' => ProgramModality::FormationInitiale,
                        'groups' => [
                            ['name' => 'LP Data Analytics', 'code' => 'LP-DATA1', 'campus' => 'RABAT', 'count' => 24],
                        ],
                    ],
                ],
            ],
            'GIL' => [
                'name' => 'Génie Industriel & Supply Chain',
                'description' => 'Logistique internationale, optimisation des processus, lean manufacturing et industrie 4.0.',
                'programs' => [
                    [
                        'code' => '1CI-INDUS',
                        'name' => '1ère Année Génie Industriel (1CI-INDUS)',
                        'modality' => ProgramModality::FormationInitiale,
                        'groups' => [
                            ['name' => '1CI Industriel - Casa', 'code' => '1IND-CASA', 'campus' => 'CASA', 'count' => 26],
                            ['name' => '1CI Industriel - Tanger', 'code' => '1IND-TNG', 'campus' => 'TANGER', 'count' => 20],
                        ],
                    ],
                    [
                        'code' => '2CI-LOG',
                        'name' => '2ème Année Supply Chain & Logistique (2CI-LOG)',
                        'modality' => ProgramModality::FormationInitiale,
                        'groups' => [
                            ['name' => '2CI Supply Chain', 'code' => '2LOG-CASA', 'campus' => 'CASA', 'count' => 22],
                        ],
                    ],
                ],
            ],
            'MF' => [
                'name' => 'Management, Finance & Audit',
                'description' => 'Ingénierie financière, audit d\'entreprise, contrôle de gestion et fintech.',
                'programs' => [
                    [
                        'code' => 'M-FIN',
                        'name' => 'Master Finance & Marchés de Capitaux',
                        'modality' => ProgramModality::FormationInitiale,
                        'groups' => [
                            ['name' => 'Master Finance - Casa', 'code' => 'MFIN-CASA', 'campus' => 'CASA', 'count' => 24],
                            ['name' => 'Master Finance - Rabat', 'code' => 'MFIN-RAB', 'campus' => 'RABAT', 'count' => 20],
                        ],
                    ],
                    [
                        'code' => 'EMBA-MGT',
                        'name' => 'Executive MBA Management & Stratégie (Temps Aménagé)',
                        'modality' => ProgramModality::TempsAmenage,
                        'groups' => [
                            ['name' => 'EMBA Management', 'code' => 'EMBA-MGT1', 'campus' => 'CASA', 'count' => 16],
                        ],
                    ],
                ],
            ],
            'RT' => [
                'name' => 'Réseaux, Télécoms & Cybersécurité',
                'description' => 'Sécurité des systèmes d\'information, réseaux sécurisés, cryptographie et cloud security.',
                'programs' => [
                    [
                        'code' => '2CI-CYBER',
                        'name' => '2ème Année Cybersécurité & Défense (2CI-CYBER)',
                        'modality' => ProgramModality::FormationInitiale,
                        'groups' => [
                            ['name' => '2CI Cybersécurité - Casa', 'code' => '2CYB-CASA', 'campus' => 'CASA', 'count' => 22],
                            ['name' => '2CI Cybersécurité - Rabat', 'code' => '2CYB-RAB', 'campus' => 'RABAT', 'count' => 20],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($departmentSpecs as $dCode => $dSpec) {
            $department = Department::firstOrCreate(
                ['code' => $dCode],
                [
                    'name' => $dSpec['name'],
                    'description' => $dSpec['description'],
                    'is_active' => true,
                ]
            );
            $this->departments[$dCode] = $department;

            foreach ($dSpec['programs'] as $pSpec) {
                $program = Program::firstOrCreate(
                    ['department_id' => $department->id, 'code' => $pSpec['code']],
                    [
                        'name' => $pSpec['name'],
                        'program_modality' => $pSpec['modality'],
                        'description' => $pSpec['name'].' dispensé en '.$pSpec['modality']->value,
                        'is_active' => true,
                    ]
                );
                $this->programs[] = $program;

                foreach ($pSpec['groups'] as $gSpec) {
                    $campus = $this->campuses[$gSpec['campus']] ?? null;

                    $group = StudentGroup::firstOrCreate(
                        ['program_id' => $program->id, 'code' => $gSpec['code'], 'academic_year' => '2026-2027'],
                        [
                            'name' => $gSpec['name'],
                            'campus_id' => $campus?->id,
                            'expected_headcount' => $gSpec['count'],
                            'is_active' => true,
                        ]
                    );
                    $this->studentGroups[] = $group;
                }
            }
        }
    }

    /**
     * Seed administrators, coordinators, and 35+ teachers with profiles.
     */
    private function seedStaffAndTeachers(): void
    {
        $this->command?->line('  → Seeding administrators, coordinators, and teachers...');

        // 1. Administrators
        User::firstOrCreate(
            ['email' => 'admin@synchro.isga.ma'],
            [
                'name' => 'Admin Synchro',
                'password' => $this->password,
                'role' => UserRole::Administrator,
                'status' => AccountStatus::Active,
                'email_verified_at' => now(),
                'activated_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin.rabat@synchro.isga.ma'],
            [
                'name' => 'Admin Rabat',
                'password' => $this->password,
                'role' => UserRole::Administrator,
                'status' => AccountStatus::Active,
                'email_verified_at' => now(),
                'activated_at' => now(),
            ]
        );

        // 2. Coordinators
        $coordSpecs = [
            ['email' => 'coordinator@synchro.isga.ma', 'name' => 'Coordinator Synchro'],
            ['email' => 'coordinator.rabat@synchro.isga.ma', 'name' => 'Coordonnateur Rabat'],
            ['email' => 'coordinator.marrakech@synchro.isga.ma', 'name' => 'Coordonnateur Marrakech'],
            ['email' => 'coordinator.tanger@synchro.isga.ma', 'name' => 'Coordonnateur Tanger'],
            ['email' => 'coordinator.isi@synchro.isga.ma', 'name' => 'Coordonnateur Filière ISI'],
        ];

        foreach ($coordSpecs as $cSpec) {
            $coord = User::firstOrCreate(
                ['email' => $cSpec['email']],
                [
                    'name' => $cSpec['name'],
                    'password' => $this->password,
                    'role' => UserRole::Coordinator,
                    'status' => AccountStatus::Active,
                    'email_verified_at' => now(),
                    'activated_at' => now(),
                ]
            );
            $this->coordinators[] = $coord;
        }

        // 3. Teachers
        $teacherNames = [
            'Pr. Karim Amrani',
            'Pr. Fatima Zahra Bennani',
            'Pr. Youssef El Mansouri',
            'Pr. Salma Tazi',
            'Dr. Amine Chraibi',
            'Pr. Hajar Alami',
            'Dr. Mehdi El Idrissi',
            'Pr. Sara Benjelloun',
            'Dr. Omar Kabbaj',
            'Pr. Kenza Berrada',
            'Dr. Anas Bouzid',
            'Pr. Nour Tahiri',
            'Pr. Othmane Lahlou',
            'Dr. Rania Chaoui',
            'Pr. Walid Fassi',
            'Dr. Zineb Naciri',
            'Pr. Adil Slaoui',
            'Pr. Meryem Alaoui',
            'Dr. Soufiane Belhaj',
            'Pr. Imane Hassani',
            'Dr. Taha Zouhir',
            'Pr. Ghita Jabri',
            'Dr. Reda Meskini',
            'Pr. Assia Gharbi',
            'Pr. Nabil Ouazzani',
            'Dr. Safaa Saidi',
            'Pr. Ismail Filali',
            'Dr. Hiba Daoudi',
            'Pr. Ayoub Qadiri',
            'Pr. Lina Cherkaoui',
            'Dr. Badr Sedrati',
            'Pr. Khadija Touhami',
            'Dr. Zakaria Hammoumi',
            'Pr. Aya Laaroussi',
            'Dr. Tariq Bennis',
            'Pr. Malak Senhaji',
        ];

        $deptKeys = array_keys($this->departments);

        foreach ($teacherNames as $idx => $name) {
            $num = $idx + 1;
            $email = "teacher{$num}@synchro.isga.ma";
            $deptKey = $deptKeys[$idx % count($deptKeys)];
            $dept = $this->departments[$deptKey];

            $teacher = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $this->password,
                    'role' => UserRole::Teacher,
                    'status' => AccountStatus::Active,
                    'email_verified_at' => now(),
                    'activated_at' => now(),
                ]
            );

            if (! $teacher->teacherProfile()->exists()) {
                $employeeNumber = 'ENS-'.str_pad((string) $teacher->id, 5, '0', STR_PAD_LEFT);
                while (TeacherProfile::query()->where('employee_number', $employeeNumber)->exists()) {
                    $employeeNumber = 'ENS-'.str_pad((string) rand(1000, 99999), 5, '0', STR_PAD_LEFT);
                }

                TeacherProfile::create([
                    'user_id' => $teacher->id,
                    'department_id' => $dept->id,
                    'employee_number' => $employeeNumber,
                    'phone' => '+2126'.rand(10000000, 99999999),
                ]);
            }

            $this->teachers[] = $teacher;
        }
    }

    /**
     * Seed 300+ students distributed across groups.
     */
    private function seedStudents(): void
    {
        $this->command?->line('  → Seeding 300+ students with student profiles...');

        $firstNames = [
            'Yassine', 'Mehdi', 'Salma', 'Amine', 'Hajar', 'Hamza', 'Sara', 'Omar', 'Kenza', 'Youssef',
            'Nour', 'Anas', 'Chaimae', 'Othmane', 'Rania', 'Walid', 'Zineb', 'Adil', 'Meryem', 'Soufiane',
            'Imane', 'Taha', 'Ghita', 'Reda', 'Assia', 'Nabil', 'Safaa', 'Ismail', 'Hiba', 'Ayoub',
            'Lina', 'Badr', 'Khadija', 'Zakaria', 'Aya', 'Tariq', 'Malak', 'Saad', 'Manal', 'Driss',
            'Asmae', 'Bilal', 'Houda', 'Marouane', 'Sofia', 'Ilyas', 'Siham', 'Khalil', 'Samia', 'Hamid',
        ];

        $lastNames = [
            'Bennani', 'El Mansouri', 'Amrani', 'Tazi', 'Alami', 'Chraibi', 'El Idrissi', 'Benjelloun',
            'Kabbaj', 'Berrada', 'Bouzid', 'Tahiri', 'Lahlou', 'Chaoui', 'Fassi', 'Naciri',
            'Slaoui', 'Alaoui', 'El Fassi', 'Belhaj', 'Hassani', 'Zouhir', 'Jabri', 'Meskini',
            'Gharbi', 'Ouazzani', 'Saidi', 'Filali', 'Daoudi', 'Qadiri', 'Cherkaoui', 'Sedrati',
        ];

        $studentCounter = 1;
        $primaryCoordinator = $this->coordinators[0];

        foreach ($this->studentGroups as $group) {
            for ($i = 0; $i < 15; $i++) {
                $firstName = $firstNames[($studentCounter + $i * 7) % count($firstNames)];
                $lastName = $lastNames[($studentCounter + $i * 11) % count($lastNames)];
                $displayName = StudentProfile::displayName($firstName, $lastName);
                $email = "student{$studentCounter}@synchro.isga.ma";

                $isInvited = ($studentCounter % 10 === 0);

                $student = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $displayName,
                        'password' => $this->password,
                        'role' => UserRole::Student,
                        'status' => $isInvited ? AccountStatus::Invited : AccountStatus::Active,
                        'email_verified_at' => $isInvited ? null : now(),
                        'activated_at' => $isInvited ? null : now(),
                    ]
                );

                if (! $student->studentProfile()->exists()) {
                    $studentNumber = 'ETU-2026-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT);
                    while (StudentProfile::query()->where('student_number', $studentNumber)->exists()) {
                        $studentNumber = 'ETU-2026-'.str_pad((string) rand(1000, 99999), 5, '0', STR_PAD_LEFT);
                    }

                    StudentProfile::create([
                        'user_id' => $student->id,
                        'student_group_id' => $group->id,
                        'student_number' => $studentNumber,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => '+2126'.rand(10000000, 99999999),
                    ]);
                }

                if ($isInvited && ! $student->invitationTokens()->exists()) {
                    InvitationToken::create([
                        'user_id' => $student->id,
                        'invited_by' => $primaryCoordinator->id,
                        'token_hash' => InvitationToken::hashToken(Str::random(64)),
                        'expires_at' => now()->addHours(InvitationToken::LIFETIME_HOURS),
                    ]);
                }

                $this->students[] = $student;
                $studentCounter++;
            }
        }
    }

    /**
     * Seed academic modules mapped to programs and teachers.
     */
    private function seedModules(): void
    {
        $this->command?->line('  → Seeding academic modules with weights and hours...');

        $moduleBlueprints = [
            '1CI' => [
                ['code' => 'ISI-101', 'name' => 'Algorithmique & Structures de Données', 'lecture' => 24, 'tp' => 20, 'weight' => 40, 'color' => '#3B82F6'],
                ['code' => 'ISI-102', 'name' => 'Programmation Orientée Objet Java', 'lecture' => 24, 'tp' => 24, 'weight' => 40, 'color' => '#10B981'],
                ['code' => 'ISI-103', 'name' => 'Architecture des Ordinateurs & Systèmes Unix', 'lecture' => 20, 'tp' => 16, 'weight' => 50, 'color' => '#8B5CF6'],
                ['code' => 'ISI-104', 'name' => 'Bases de Données Relationnelles & SQL', 'lecture' => 20, 'tp' => 20, 'weight' => 40, 'color' => '#F59E0B'],
                ['code' => 'ISI-105', 'name' => 'Fondements des Réseaux & Modèle OSI', 'lecture' => 20, 'tp' => 16, 'weight' => 50, 'color' => '#06B6D4'],
                ['code' => 'ISI-106', 'name' => 'Mathématiques Appliquées & Probabilités', 'lecture' => 26, 'tp' => 12, 'weight' => 30, 'color' => '#EC4899'],
            ],
            '2CI' => [
                ['code' => 'DEV-201', 'name' => 'Architecture Web Moderne (Laravel, Inertia, React)', 'lecture' => 24, 'tp' => 26, 'weight' => 50, 'color' => '#EF4444'],
                ['code' => 'DEV-202', 'name' => 'Conception Logicielle & Design Patterns GoF', 'lecture' => 22, 'tp' => 18, 'weight' => 40, 'color' => '#6366F1'],
                ['code' => 'DEV-203', 'name' => 'Microservices, API RESTful & GraphQL', 'lecture' => 20, 'tp' => 20, 'weight' => 40, 'color' => '#14B8A6'],
                ['code' => 'DEV-204', 'name' => 'Tests Automatisés, TDD & Qualité Logicielle', 'lecture' => 16, 'tp' => 20, 'weight' => 50, 'color' => '#F97316'],
            ],
            '3CI-CLOUD' => [
                ['code' => 'CLD-301', 'name' => 'Cloud Computing & Infrastructure AWS/Azure', 'lecture' => 24, 'tp' => 24, 'weight' => 50, 'color' => '#0EA5E9'],
                ['code' => 'CLD-302', 'name' => 'Conteneurisation Docker & Orchestration Kubernetes', 'lecture' => 20, 'tp' => 24, 'weight' => 50, 'color' => '#84CC16'],
                ['code' => 'CLD-303', 'name' => 'CI/CD Pipelines, GitOps & Infrastructure as Code', 'lecture' => 18, 'tp' => 22, 'weight' => 40, 'color' => '#A855F7'],
            ],
            'M-MIAGE-TA' => [
                ['code' => 'MIA-401', 'name' => 'Management des Systèmes d\'Information', 'lecture' => 26, 'tp' => 14, 'weight' => 40, 'color' => '#3B82F6'],
                ['code' => 'MIA-402', 'name' => 'Audit Informatique & Sécurité Organisationnelle', 'lecture' => 22, 'tp' => 16, 'weight' => 40, 'color' => '#10B981'],
                ['code' => 'MIA-403', 'name' => 'Gestion de Projet Agile Scrum & PMBOK', 'lecture' => 20, 'tp' => 16, 'weight' => 50, 'color' => '#F59E0B'],
                ['code' => 'MIA-404', 'name' => 'ERP & Systèmes Intégrés d\'Entreprise SAP', 'lecture' => 20, 'tp' => 20, 'weight' => 40, 'color' => '#6366F1'],
            ],
            '2CI-DATA' => [
                ['code' => 'DAT-201', 'name' => 'Machine Learning Supervisé & Non Supervisé', 'lecture' => 24, 'tp' => 24, 'weight' => 50, 'color' => '#EC4899'],
                ['code' => 'DAT-202', 'name' => 'Deep Learning, CNN & Réseaux de Neurones', 'lecture' => 22, 'tp' => 22, 'weight' => 40, 'color' => '#8B5CF6'],
                ['code' => 'DAT-203', 'name' => 'Big Data Processing avec Apache Spark & PySpark', 'lecture' => 20, 'tp' => 24, 'weight' => 50, 'color' => '#F97316'],
                ['code' => 'DAT-204', 'name' => 'Traitement Automatique du Langage Naturel (NLP)', 'lecture' => 20, 'tp' => 20, 'weight' => 40, 'color' => '#06B6D4'],
            ],
            'EMBA-IA' => [
                ['code' => 'EMB-501', 'name' => 'Stratégie d\'Adoption de l\'IA & Éthique des Données', 'lecture' => 24, 'tp' => 12, 'weight' => 50, 'color' => '#3B82F6'],
                ['code' => 'EMB-502', 'name' => 'IA Générative, LLMs & Automatisation Métier', 'lecture' => 22, 'tp' => 16, 'weight' => 50, 'color' => '#10B981'],
            ],
            'LP-DATA' => [
                ['code' => 'LPD-101', 'name' => 'Python pour la Data & Bibliothèques Pandas/Numpy', 'lecture' => 20, 'tp' => 24, 'weight' => 50, 'color' => '#F59E0B'],
                ['code' => 'LPD-102', 'name' => 'Visualisation de Données & Dashboards PowerBI', 'lecture' => 18, 'tp' => 22, 'weight' => 50, 'color' => '#6366F1'],
            ],
            '1CI-INDUS' => [
                ['code' => 'IND-101', 'name' => 'Recherche Opérationnelle & Optimisation Linéaire', 'lecture' => 24, 'tp' => 16, 'weight' => 40, 'color' => '#14B8A6'],
                ['code' => 'IND-102', 'name' => 'Gestion de Production, Lean & Méthode 5S', 'lecture' => 22, 'tp' => 18, 'weight' => 40, 'color' => '#EC4899'],
                ['code' => 'IND-103', 'name' => 'Contrôle Qualité Six Sigma & Métrologie', 'lecture' => 20, 'tp' => 16, 'weight' => 50, 'color' => '#8B5CF6'],
            ],
            '2CI-LOG' => [
                ['code' => 'LOG-201', 'name' => 'Supply Chain Globale & Gestion des Flux', 'lecture' => 24, 'tp' => 18, 'weight' => 40, 'color' => '#0EA5E9'],
                ['code' => 'LOG-202', 'name' => 'Transport International, Douanes & Incoterms', 'lecture' => 22, 'tp' => 14, 'weight' => 40, 'color' => '#F97316'],
            ],
            'M-FIN' => [
                ['code' => 'FIN-301', 'name' => 'Évaluation d\'Entreprise, Fusions & Acquisitions', 'lecture' => 24, 'tp' => 16, 'weight' => 40, 'color' => '#10B981'],
                ['code' => 'FIN-302', 'name' => 'Marchés Financiers, Produits Dérivés & Risques', 'lecture' => 24, 'tp' => 16, 'weight' => 50, 'color' => '#3B82F6'],
                ['code' => 'FIN-303', 'name' => 'Audit Financier & Normes IFRS', 'lecture' => 20, 'tp' => 16, 'weight' => 40, 'color' => '#F59E0B'],
            ],
            'EMBA-MGT' => [
                ['code' => 'MGT-501', 'name' => 'Leadership, Négociation & Management Stratégique', 'lecture' => 26, 'tp' => 10, 'weight' => 50, 'color' => '#6366F1'],
            ],
            '2CI-CYBER' => [
                ['code' => 'CYB-201', 'name' => 'Sécurité Offensive, Pentesting & Ethical Hacking', 'lecture' => 22, 'tp' => 26, 'weight' => 50, 'color' => '#EF4444'],
                ['code' => 'CYB-202', 'name' => 'Sécurité Défensive, SOC, SIEM & Incident Response', 'lecture' => 20, 'tp' => 24, 'weight' => 50, 'color' => '#8B5CF6'],
                ['code' => 'CYB-203', 'name' => 'Cryptographie Appliquée & Protocoles Sécurisés', 'lecture' => 24, 'tp' => 16, 'weight' => 40, 'color' => '#06B6D4'],
            ],
        ];

        $teacherIndex = 0;

        foreach ($this->programs as $program) {
            $blueprints = $moduleBlueprints[$program->code] ?? [];

            foreach ($blueprints as $bp) {
                $teacher = $this->teachers[$teacherIndex % count($this->teachers)];
                $teacherIndex++;

                $totalHours = $bp['lecture'] + $bp['tp'];

                $module = Module::firstOrCreate(
                    ['program_id' => $program->id, 'code' => $bp['code']],
                    [
                        'teacher_id' => $teacher->id,
                        'name' => $bp['name'],
                        'total_hours' => $totalHours,
                        'lecture_hours' => $bp['lecture'],
                        'tp_hours' => $bp['tp'],
                        'continuous_assessment_weight' => $bp['weight'],
                        'color_code' => $bp['color'],
                        'description' => 'Module '.$bp['name'].' dispensé dans le cadre du programme '.$program->name,
                        'is_active' => true,
                    ]
                );

                $this->modules[] = $module;
            }
        }
    }

    /**
     * Seed teacher unavailabilities (recurring and ad-hoc).
     */
    private function seedTeacherUnavailabilities(): void
    {
        $this->command?->line('  → Seeding teacher unavailabilities (recurring & ad-hoc)...');

        $reasons = [
            'Recherche doctorale et encadrement de thèse à l\'université.',
            'Participation au séminaire international de recherche appliquée.',
            'Déplacement académique pour conférence et jury d\'examen.',
            'Charge d\'enseignement auprès de l\'établissement partenaire.',
            'Journée réservée aux consultations médicales et familiales.',
            'Mission d\'expertise et audit externe en entreprise.',
            'Animation d\'ateliers de formation continue pour professionnels.',
        ];

        $reviewers = $this->coordinators;

        foreach ($this->teachers as $tIdx => $teacher) {
            if ($tIdx >= 25) {
                continue;
            }

            if ($teacher->unavailabilities()->exists()) {
                continue;
            }

            $reviewer = $reviewers[$tIdx % count($reviewers)];

            $dayOfWeek = ($tIdx % 5) + 1;
            $isAfternoon = ($tIdx % 2 === 0);
            $status = match ($tIdx % 4) {
                0, 1 => UnavailabilityStatus::Approved,
                2 => UnavailabilityStatus::Pending,
                default => UnavailabilityStatus::Rejected,
            };

            TeacherUnavailability::create([
                'teacher_id' => $teacher->id,
                'type' => UnavailabilityType::RecurringWeekly,
                'day_of_week' => $dayOfWeek,
                'start_date' => Carbon::parse('2026-09-01'),
                'end_date' => Carbon::parse('2027-06-30'),
                'start_time' => $isAfternoon ? '14:00' : '08:30',
                'end_time' => $isAfternoon ? '18:15' : '12:45',
                'reason' => $reasons[$tIdx % count($reasons)],
                'status' => $status,
                'reviewed_by' => $status !== UnavailabilityStatus::Pending ? $reviewer->id : null,
                'reviewed_at' => $status !== UnavailabilityStatus::Pending ? now()->subDays(rand(5, 20)) : null,
                'review_note' => $status === UnavailabilityStatus::Approved
                    ? 'Demande validée en coordination pédagogique.'
                    : ($status === UnavailabilityStatus::Rejected ? 'Créneau incompatible avec les cours prioritaires.' : null),
            ]);

            if ($tIdx % 3 === 0) {
                $startOffset = rand(10, 45);
                $adHocDate = Carbon::parse('2026-10-02')->addDays($startOffset);

                TeacherUnavailability::create([
                    'teacher_id' => $teacher->id,
                    'type' => UnavailabilityType::AdHocDate,
                    'day_of_week' => null,
                    'start_date' => $adHocDate->copy()->startOfDay(),
                    'end_date' => $adHocDate->copy()->addDays(rand(1, 3))->startOfDay(),
                    'start_time' => null,
                    'end_time' => null,
                    'reason' => 'Déplacement mission académique à l\'étranger.',
                    'status' => UnavailabilityStatus::Approved,
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now()->subDays(2),
                    'review_note' => 'Mission validée par la direction.',
                ]);
            }
        }
    }

    /**
     * Seed 250+ course sessions and student attendance registers.
     */
    private function seedCourseSessionsAndAttendance(): void
    {
        $this->command?->line('  → Seeding 250+ course sessions and student attendance registers...');

        $timeSlots = [
            ['08:30', '10:30'],
            ['10:45', '12:45'],
            ['14:00', '16:00'],
            ['16:15', '18:15'],
        ];

        $eveningSlot = ['18:30', '20:30'];

        $baseMonday = CarbonImmutable::parse('2026-09-14')->startOfWeek();
        $sessionCounter = 0;
        $primaryCoordinator = $this->coordinators[0];

        foreach ($this->studentGroups as $gIdx => $group) {
            $program = $group->program;
            $groupModules = array_values(array_filter($this->modules, fn (Module $m) => $m->program_id === $program->id));

            if (empty($groupModules)) {
                continue;
            }

            $isTempsAmenage = ($program->program_modality === ProgramModality::TempsAmenage);

            for ($week = 0; $week < 6; $week++) {
                $weekStart = $baseMonday->addWeeks($week);

                foreach ($groupModules as $mIdx => $module) {
                    $dayOffset = ($mIdx + $gIdx) % 5;
                    $dayDate = $weekStart->addDays($dayOffset);

                    $slot = $isTempsAmenage ? $eveningSlot : $timeSlots[$mIdx % count($timeSlots)];

                    $startsAt = $dayDate->setTimeFromTimeString($slot[0]);
                    $endsAt = $dayDate->setTimeFromTimeString($slot[1]);

                    $room = $this->rooms[($gIdx * 3 + $mIdx + $week) % count($this->rooms)];

                    $session = CourseSession::firstOrCreate([
                        'module_id' => $module->id,
                        'teacher_id' => $module->teacher_id ?? $this->teachers[0]->id,
                        'room_id' => $room->id,
                        'starts_at' => $startsAt,
                    ], [
                        'ends_at' => $endsAt,
                    ]);

                    $session->studentGroups()->syncWithoutDetaching([$group->id]);
                    $sessionCounter++;

                    if ($endsAt->lessThan(Carbon::parse('2026-10-02 21:00:00'))) {
                        $groupStudents = User::query()
                            ->whereHas('studentProfile', fn ($q) => $q->where('student_group_id', $group->id))
                            ->get();

                        foreach ($groupStudents as $stIdx => $student) {
                            $rand = ($session->id * 17 + $stIdx * 31) % 100;
                            $status = AttendanceStatus::Present;
                            $remarks = null;

                            if ($rand > 92) {
                                $status = AttendanceStatus::Absent;
                                $remarks = 'Absence non justifiée.';
                            } elseif ($rand > 87) {
                                $status = AttendanceStatus::Late;
                                $remarks = 'Retard de 15 minutes.';
                            } elseif ($rand > 84) {
                                $status = AttendanceStatus::Excused;
                                $remarks = 'Certificat médical transmis à la scolarité.';
                            }

                            SessionAttendance::firstOrCreate([
                                'course_session_id' => $session->id,
                                'student_id' => $student->id,
                            ], [
                                'status' => $status,
                                'remarks' => $remarks,
                                'recorded_by' => $session->teacher_id,
                            ]);
                        }
                    }

                    if ($sessionCounter % 40 === 0 && ! ConflictOverride::query()->where('schedulable_id', $session->id)->exists()) {
                        ConflictOverride::create([
                            'user_id' => $primaryCoordinator->id,
                            'schedulable_type' => CourseSession::class,
                            'schedulable_id' => $session->id,
                            'conflict_type' => ConflictType::Capacity,
                            'justification' => 'Capacité de salle temporairement tolérée pour TD dédoublé.',
                            'details' => ['room_capacity' => $room->course_capacity, 'expected_count' => $group->expected_headcount],
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Seed exam periods, exams, allocations, deliberations, and grades.
     */
    private function seedExamPeriodsAndExams(): void
    {
        $this->command?->line('  → Seeding exam periods, exams, allocations, deliberations, and grades...');

        $coordinator = $this->coordinators[0];

        $p1 = ExamPeriod::firstOrCreate(
            ['name' => 'Session d\'Évaluation de Rentrée - Septembre 2026'],
            [
                'session_type' => ExamSessionType::Normal,
                'academic_year' => '2026-2027',
                'start_date' => Carbon::parse('2026-09-14'),
                'end_date' => Carbon::parse('2026-09-25'),
            ]
        );

        $p2 = ExamPeriod::firstOrCreate(
            ['name' => 'Session de Rattrapage de Rentrée - Fin Septembre 2026'],
            [
                'session_type' => ExamSessionType::Rattrapage,
                'academic_year' => '2026-2027',
                'start_date' => Carbon::parse('2026-09-28'),
                'end_date' => Carbon::parse('2026-10-01'),
            ]
        );

        $p3 = ExamPeriod::firstOrCreate(
            ['name' => 'Examens Mi-Semestre Automne 2026'],
            [
                'session_type' => ExamSessionType::Normal,
                'academic_year' => '2026-2027',
                'start_date' => Carbon::parse('2026-10-26'),
                'end_date' => Carbon::parse('2026-11-06'),
            ]
        );

        $p4 = ExamPeriod::firstOrCreate(
            ['name' => 'Examens Finaux Semestre 1 - Janvier 2027'],
            [
                'session_type' => ExamSessionType::Normal,
                'academic_year' => '2026-2027',
                'start_date' => Carbon::parse('2027-01-11'),
                'end_date' => Carbon::parse('2027-01-22'),
            ]
        );

        $examModules = array_slice($this->modules, 0, 16);

        foreach ($examModules as $eIdx => $module) {
            $program = $module->program;
            $group = StudentGroup::query()->where('program_id', $program->id)->first();

            if (! $group) {
                continue;
            }

            if ($eIdx < 6) {
                $startsAt = Carbon::parse('2026-09-15 09:00:00')->addDays($eIdx);
                $endsAt = $startsAt->copy()->addHours(2);

                $exam = Exam::firstOrCreate([
                    'exam_period_id' => $p1->id,
                    'module_id' => $module->id,
                    'starts_at' => $startsAt,
                ], [
                    'ends_at' => $endsAt,
                    'state' => ExamState::Completed,
                    'force_single_room' => false,
                    'published_at' => Carbon::parse('2026-09-08 10:00:00'),
                    'published_by' => $coordinator->id,
                ]);

                $exam->studentGroups()->syncWithoutDetaching([$group->id]);
                $this->setupExamAllocationAndDeliberation($exam, $group, $coordinator, isLocked: true);
            } elseif ($eIdx < 9) {
                $startsAt = Carbon::parse('2026-09-29 09:00:00')->addDays($eIdx - 6);
                $endsAt = $startsAt->copy()->addHours(2);

                $exam = Exam::firstOrCreate([
                    'exam_period_id' => $p2->id,
                    'module_id' => $module->id,
                    'starts_at' => $startsAt,
                ], [
                    'ends_at' => $endsAt,
                    'state' => ExamState::Completed,
                    'force_single_room' => false,
                    'published_at' => Carbon::parse('2026-09-26 10:00:00'),
                    'published_by' => $coordinator->id,
                ]);

                $exam->studentGroups()->syncWithoutDetaching([$group->id]);
                $this->setupExamAllocationAndDeliberation($exam, $group, $coordinator, isLocked: false);
            } elseif ($eIdx < 13) {
                $startsAt = Carbon::parse('2026-10-27 09:00:00')->addDays($eIdx - 9);
                $endsAt = $startsAt->copy()->addHours(2);

                $exam = Exam::firstOrCreate([
                    'exam_period_id' => $p3->id,
                    'module_id' => $module->id,
                    'starts_at' => $startsAt,
                ], [
                    'ends_at' => $endsAt,
                    'state' => ExamState::Published,
                    'force_single_room' => false,
                    'published_at' => Carbon::parse('2026-10-01 12:00:00'),
                    'published_by' => $coordinator->id,
                ]);

                $exam->studentGroups()->syncWithoutDetaching([$group->id]);
                $this->setupExamAllocationUpcoming($exam, $group);
            } else {
                $startsAt = Carbon::parse('2027-01-12 09:00:00')->addDays($eIdx - 13);
                $endsAt = $startsAt->copy()->addHours(2);

                $exam = Exam::firstOrCreate([
                    'exam_period_id' => $p4->id,
                    'module_id' => $module->id,
                    'starts_at' => $startsAt,
                ], [
                    'ends_at' => $endsAt,
                    'state' => ExamState::Draft,
                    'force_single_room' => false,
                ]);

                $exam->studentGroups()->syncWithoutDetaching([$group->id]);
            }
        }
    }

    /**
     * Allocate rooms, invigilators, candidates, grades, and deliberation for a completed exam.
     */
    private function setupExamAllocationAndDeliberation(Exam $exam, StudentGroup $group, User $coordinator, bool $isLocked): void
    {
        $groupStudents = User::query()
            ->whereHas('studentProfile', fn ($q) => $q->where('student_group_id', $group->id))
            ->get();

        if ($groupStudents->isEmpty()) {
            return;
        }

        $room = $this->rooms[$exam->id % count($this->rooms)];

        $assignment = ExamRoomAssignment::firstOrCreate(
            ['exam_id' => $exam->id, 'room_id' => $room->id],
            [
                'position' => 1,
                'allocated_students_count' => $groupStudents->count(),
                'first_surname' => 'A',
                'last_surname' => 'Z',
            ]
        );

        $leadTeacher = $this->teachers[$exam->id % count($this->teachers)];
        $assistantTeacher = $this->teachers[($exam->id + 1) % count($this->teachers)];

        ExamInvigilator::firstOrCreate(
            ['exam_id' => $exam->id, 'teacher_id' => $leadTeacher->id],
            [
                'exam_room_assignment_id' => $assignment->id,
                'role' => InvigilatorRole::Principal->value,
            ]
        );

        if ($leadTeacher->id !== $assistantTeacher->id) {
            ExamInvigilator::firstOrCreate(
                ['exam_id' => $exam->id, 'teacher_id' => $assistantTeacher->id],
                [
                    'exam_room_assignment_id' => $assignment->id,
                    'role' => InvigilatorRole::Adjoint->value,
                ]
            );
        }

        foreach ($groupStudents as $sIdx => $student) {
            ExamCandidate::firstOrCreate(
                ['exam_id' => $exam->id, 'student_id' => $student->id],
                [
                    'exam_room_assignment_id' => $assignment->id,
                    'seat_number' => $sIdx + 1,
                    'convocation_uuid' => (string) Str::uuid(),
                    'checked_in_at' => $exam->starts_at->copy()->addMinutes(rand(5, 20)),
                    'checked_in_by' => $leadTeacher->id,
                ]
            );
        }

        $deliberation = ExamDeliberation::firstOrCreate(
            ['exam_id' => $exam->id],
            [
                'status' => GradeSheetStatus::Draft,
                'submitted_at' => $exam->ends_at->copy()->addDays(2),
                'submitted_by' => $leadTeacher->id,
            ]
        );

        $weight = $exam->module->continuous_assessment_weight;
        $totalFinal = 0.0;
        $passCount = 0;

        foreach ($groupStudents as $stIdx => $student) {
            $isAbsent = ($stIdx === 14);
            $ccGrade = $isAbsent ? null : number_format(rand(100, 190) / 10, 2);
            $examGrade = $isAbsent ? null : number_format(rand(80, 185) / 10, 2);

            $finalGrade = null;
            if (! $isAbsent && $ccGrade !== null && $examGrade !== null) {
                $final = (((float) $ccGrade * $weight) + ((float) $examGrade * (100 - $weight))) / 100.0;
                $finalGrade = number_format($final, 2);
                $totalFinal += $final;
                if ($final >= 10.0) {
                    $passCount++;
                }
            }

            if (! ExamGrade::query()->where('exam_id', $exam->id)->where('student_id', $student->id)->exists()) {
                ExamGrade::query()->insert([
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                    'continuous_assessment_grade' => $ccGrade,
                    'exam_grade' => $examGrade,
                    'final_grade' => $finalGrade,
                    'previous_final_grade' => null,
                    'is_absent' => $isAbsent,
                    'remarks' => $isAbsent ? 'Absent justifié.' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $evaluatedCount = count($groupStudents) - 1;
        $classAverage = $evaluatedCount > 0 ? round($totalFinal / $evaluatedCount, 2) : 13.5;
        $passRate = $evaluatedCount > 0 ? round(($passCount / $evaluatedCount) * 100, 2) : 85.0;

        if ($deliberation->status === GradeSheetStatus::Locked) {
            return;
        }

        if ($isLocked) {
            $deliberation->update([
                'status' => GradeSheetStatus::Locked,
                'locked_at' => $exam->ends_at->copy()->addDays(4),
                'locked_by' => $coordinator->id,
                'continuous_assessment_weight' => $weight,
                'class_average' => $classAverage,
                'pass_rate' => $passRate,
                'pv_document_path' => "deliberation-pvs/{$exam->id}.pdf",
                'pv_sha256' => hash('sha256', "pv-sample-content-{$exam->id}"),
            ]);
        } elseif ($deliberation->status === GradeSheetStatus::Draft) {
            $deliberation->update([
                'status' => GradeSheetStatus::Submitted,
            ]);
        }
    }

    /**
     * Allocate rooms, invigilators, and candidates for an upcoming published exam.
     */
    private function setupExamAllocationUpcoming(Exam $exam, StudentGroup $group): void
    {
        $groupStudents = User::query()
            ->whereHas('studentProfile', fn ($q) => $q->where('student_group_id', $group->id))
            ->get();

        if ($groupStudents->isEmpty()) {
            return;
        }

        $room = $this->rooms[($exam->id + 2) % count($this->rooms)];

        $assignment = ExamRoomAssignment::firstOrCreate(
            ['exam_id' => $exam->id, 'room_id' => $room->id],
            [
                'position' => 1,
                'allocated_students_count' => $groupStudents->count(),
                'first_surname' => 'A',
                'last_surname' => 'Z',
            ]
        );

        $leadTeacher = $this->teachers[($exam->id + 3) % count($this->teachers)];

        ExamInvigilator::firstOrCreate(
            ['exam_id' => $exam->id, 'teacher_id' => $leadTeacher->id],
            [
                'exam_room_assignment_id' => $assignment->id,
                'role' => InvigilatorRole::Principal->value,
            ]
        );

        foreach ($groupStudents as $sIdx => $student) {
            ExamCandidate::firstOrCreate(
                ['exam_id' => $exam->id, 'student_id' => $student->id],
                [
                    'exam_room_assignment_id' => $assignment->id,
                    'seat_number' => $sIdx + 1,
                    'convocation_uuid' => (string) Str::uuid(),
                    'checked_in_at' => null,
                    'checked_in_by' => null,
                ]
            );
        }
    }

    /**
     * Seed spreadsheet import audit history.
     */
    private function seedSpreadsheetImports(): void
    {
        $this->command?->line('  → Seeding spreadsheet import audit history...');

        $admin = User::firstOrCreate(['email' => 'admin@synchro.isga.ma']);

        $imports = [
            [
                'type' => ImportType::Rooms,
                'status' => ImportStatus::Succeeded,
                'original_filename' => 'referentiel_salles_casablanca_2026.xlsx',
                'imported_count' => 24,
                'error_count' => 0,
                'errors' => null,
                'started_at' => Carbon::parse('2026-09-01 10:00:00'),
                'finished_at' => Carbon::parse('2026-09-01 10:01:15'),
            ],
            [
                'type' => ImportType::Teachers,
                'status' => ImportStatus::Succeeded,
                'original_filename' => 'corps_enseignant_permanent_2026.xlsx',
                'imported_count' => 35,
                'error_count' => 0,
                'errors' => null,
                'started_at' => Carbon::parse('2026-09-02 11:30:00'),
                'finished_at' => Carbon::parse('2026-09-02 11:31:40'),
            ],
            [
                'type' => ImportType::Modules,
                'status' => ImportStatus::Succeeded,
                'original_filename' => 'maquette_pedagogique_isi_2026.xlsx',
                'imported_count' => 28,
                'error_count' => 0,
                'errors' => null,
                'started_at' => Carbon::parse('2026-09-03 14:00:00'),
                'finished_at' => Carbon::parse('2026-09-03 14:01:20'),
            ],
            [
                'type' => ImportType::Students,
                'status' => ImportStatus::Succeeded,
                'original_filename' => 'inscriptions_1ci_promo_2026.xlsx',
                'imported_count' => 120,
                'error_count' => 0,
                'errors' => null,
                'started_at' => Carbon::parse('2026-09-05 09:15:00'),
                'finished_at' => Carbon::parse('2026-09-05 09:18:30'),
            ],
            [
                'type' => ImportType::Students,
                'status' => ImportStatus::Failed,
                'original_filename' => 'inscriptions_reprise_etudes_errone.xlsx',
                'imported_count' => 8,
                'error_count' => 3,
                'errors' => [
                    ['row' => 4, 'column' => 'email', 'message' => 'L\'adresse email existe déjà dans le système.'],
                    ['row' => 7, 'column' => 'student_group_code', 'message' => 'Le code groupe [3CI-UNKNOWN] est introuvable.'],
                    ['row' => 12, 'column' => 'first_name', 'message' => 'Le prénom est requis et ne peut pas être vide.'],
                ],
                'started_at' => Carbon::parse('2026-09-06 16:00:00'),
                'finished_at' => Carbon::parse('2026-09-06 16:00:45'),
            ],
            [
                'type' => ImportType::Rooms,
                'status' => ImportStatus::Succeeded,
                'original_filename' => 'campus_rabat_extensions_2026.xlsx',
                'imported_count' => 12,
                'error_count' => 0,
                'errors' => null,
                'started_at' => Carbon::parse('2026-09-10 08:45:00'),
                'finished_at' => Carbon::parse('2026-09-10 08:45:50'),
            ],
            [
                'type' => ImportType::Teachers,
                'status' => ImportStatus::Succeeded,
                'original_filename' => 'vacataires_temps_amenage_2026.xlsx',
                'imported_count' => 10,
                'error_count' => 0,
                'errors' => null,
                'started_at' => Carbon::parse('2026-09-12 17:00:00'),
                'finished_at' => Carbon::parse('2026-09-12 17:01:05'),
            ],
        ];

        foreach ($imports as $imp) {
            SpreadsheetImport::firstOrCreate(
                ['original_filename' => $imp['original_filename']],
                [
                    'user_id' => $admin->id,
                    'type' => $imp['type'],
                    'status' => $imp['status'],
                    'disk' => 'local',
                    'path' => 'imports/'.Str::slug(pathinfo($imp['original_filename'], PATHINFO_FILENAME)).'.xlsx',
                    'extension' => 'xlsx',
                    'imported_count' => $imp['imported_count'],
                    'error_count' => $imp['error_count'],
                    'errors' => $imp['errors'],
                    'started_at' => $imp['started_at'],
                    'finished_at' => $imp['finished_at'],
                ]
            );
        }
    }
}
