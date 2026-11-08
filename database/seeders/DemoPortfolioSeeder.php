<?php

namespace Database\Seeders;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Enums\ProgressionStatus;
use App\Enums\ProgressionType;
use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\GradingPolicy;
use App\Models\GradingPolicyComponent;
use App\Models\HomeroomAssignment;
use App\Models\Notification;
use App\Models\ReportCard;
use App\Models\ReportCardItem;
use App\Models\ScheduleSlot;
use App\Models\Score;
use App\Models\ScoringComponent;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\StudentProgression;
use App\Models\Subject;
use App\Models\SystemSetting;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoPortfolioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('Menyiapkan Data Portofolio Sintetis Realistis MI Alghivari...');

        $now = Carbon::now();
        $password = Hash::make('Password123!');

        // -------------------------------------------------------------
        // 1. PENGATURAN SISTEM (System Settings)
        // -------------------------------------------------------------
        $settings = [
            'school_name' => 'Madrasah Ibtidaiyah Alghivari',
            'school_address' => 'Jl. Babakan Ciparay No. 123, Pasirkoja, Kota Bandung, Jawa Barat 40222',
            'school_phone' => '(022) 540-1234',
            'school_timezone' => 'Asia/Jakarta',
            'school_principal_name' => 'Drs. H. Miftah Faridl, M.Ag.',
            'school_npsn' => '60728912',
            'school_nsm' => '111232730045',
        ];

        foreach ($settings as $key => $val) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $val]);
        }

        // -------------------------------------------------------------
        // 2. AKUN PENGGUNA DEMO MULTI-ROLE
        // -------------------------------------------------------------
        $usersData = [
            // Master Admin
            [
                'name' => 'Administrator Utama',
                'username' => 'admin',
                'email' => 'admin@alghivari.sch.id',
                'role' => Role::MASTER_ADMIN,
            ],
            // Academic Admin
            [
                'name' => 'Staf Tata Usaha & Akademik',
                'username' => 'akademik',
                'email' => 'akademik@alghivari.sch.id',
                'role' => Role::ACADEMIC_ADMIN,
            ],
            // Data Entry Operator
            [
                'name' => 'Operator Data Entri',
                'username' => 'dataentry',
                'email' => 'dataentry@alghivari.sch.id',
                'role' => Role::DATA_ENTRY_ADMIN,
            ],
            // Kepala Madrasah (Principal)
            [
                'name' => 'Drs. H. Miftah Faridl, M.Ag.',
                'username' => 'kepala.madrasah',
                'email' => 'kamad@alghivari.sch.id',
                'role' => Role::PRINCIPAL,
            ],
            // Guru Mapel & Wali Kelas
            [
                'name' => 'Ustadz Ahmad Fauzi, S.Pd.I.',
                'username' => 'guru.ahmad',
                'email' => 'ahmad.fauzi@alghivari.sch.id',
                'role' => Role::TEACHER,
            ],
            [
                'name' => 'Ustadzah Siti Maryam, S.Pd.',
                'username' => 'guru.siti',
                'email' => 'siti.maryam@alghivari.sch.id',
                'role' => Role::TEACHER,
            ],
            [
                'name' => 'Ustadz Budi Santoso, M.Pd.',
                'username' => 'guru.budi',
                'email' => 'budi.santoso@alghivari.sch.id',
                'role' => Role::TEACHER,
            ],
            [
                'name' => 'Ustadzah Aisyah Rahma, S.Pd.',
                'username' => 'guru.aisyah',
                'email' => 'aisyah.rahma@alghivari.sch.id',
                'role' => Role::TEACHER,
            ],
            [
                'name' => 'Ustadz Zainal Arifin, S.Ag.',
                'username' => 'guru.zainal',
                'email' => 'zainal.arifin@alghivari.sch.id',
                'role' => Role::TEACHER,
            ],
            [
                'name' => 'Ustadz Ridwan Nugraha, S.Pd.',
                'username' => 'guru.ridwan',
                'email' => 'ridwan.nugraha@alghivari.sch.id',
                'role' => Role::TEACHER,
            ],
            // Wali Murid (Guardians)
            [
                'name' => 'Hasan Basri, S.T.',
                'username' => 'wali.hasan',
                'email' => 'hasan.basri@example.test',
                'role' => Role::GUARDIAN,
            ],
            [
                'name' => 'Dewi Sartika, S.E.',
                'username' => 'wali.dewi',
                'email' => 'dewi.sartika@example.test',
                'role' => Role::GUARDIAN,
            ],
            [
                'name' => 'Nurul Hidayah, S.Sos.',
                'username' => 'wali.nurul',
                'email' => 'nurul.hidayah@example.test',
                'role' => Role::GUARDIAN,
            ],
            [
                'name' => 'Agus Setiawan',
                'username' => 'wali.agus',
                'email' => 'agus.setiawan@example.test',
                'role' => Role::GUARDIAN,
            ],
            [
                'name' => 'Hendra Wijaya',
                'username' => 'wali.hendra',
                'email' => 'hendra.wijaya@example.test',
                'role' => Role::GUARDIAN,
            ],
            [
                'name' => 'Rina Marlina',
                'username' => 'wali.rina',
                'email' => 'rina.marlina@example.test',
                'role' => Role::GUARDIAN,
            ],
        ];

        $users = [];
        foreach ($usersData as $u) {
            $user = User::updateOrCreate(
                ['username' => $u['username']],
                [
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'password' => $password,
                    'must_change_password' => false,
                    'password_changed_at' => $now,
                    'is_active' => true,
                    'credential_version' => 1,
                    'session_generation' => 1,
                ]
            );
            $user->assignRole($u['role']);
            $users[$u['username']] = $user;
        }

        $masterAdmin = $users['admin'];
        $academicAdmin = $users['akademik'];
        $principal = $users['kepala.madrasah'];
        $teachers = [
            $users['guru.ahmad'],
            $users['guru.siti'],
            $users['guru.budi'],
            $users['guru.aisyah'],
            $users['guru.zainal'],
            $users['guru.ridwan'],
        ];
        // Exclusive list of homeroom teachers (guru.ridwan is preserved purely as Guru Mengajar / Subject Teacher)
        $homeroomTeachers = [
            $users['guru.ahmad'],
            $users['guru.siti'],
            $users['guru.budi'],
            $users['guru.aisyah'],
            $users['guru.zainal'],
        ];
        $guardians = [
            $users['wali.hasan'],
            $users['wali.dewi'],
            $users['wali.nurul'],
            $users['wali.agus'],
            $users['wali.hendra'],
            $users['wali.rina'],
        ];

        // -------------------------------------------------------------
        // 3. STRUKTUR AKADEMIK (Tahun Ajaran & Semester)
        // -------------------------------------------------------------
        // Tahun Lampau (Arsip): 2025/2026
        $yearPast = AcademicYear::firstOrCreate(
            ['name' => '2025/2026'],
            ['starts_on' => '2025-07-14', 'ends_on' => '2026-06-20']
        );
        $termPastGanjil = Term::firstOrCreate(
            ['academic_year_id' => $yearPast->id, 'semester' => 1],
            ['starts_on' => '2025-07-14', 'ends_on' => '2025-12-20', 'status' => 'closed']
        );
        $termPastGenap = Term::firstOrCreate(
            ['academic_year_id' => $yearPast->id, 'semester' => 2],
            ['starts_on' => '2026-01-05', 'ends_on' => '2026-06-20', 'status' => 'closed']
        );

        // Tahun Aktif: 2026/2027
        $yearActive = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            ['starts_on' => '2026-07-13', 'ends_on' => '2027-06-19']
        );
        $termActiveGanjil = Term::firstOrCreate(
            ['academic_year_id' => $yearActive->id, 'semester' => 1],
            ['starts_on' => '2026-07-13', 'ends_on' => '2026-12-19', 'status' => 'active']
        );

        // -------------------------------------------------------------
        // 4. MATA PELAJARAN RESMI MI (Kemenag & Kurikulum Merdeka)
        // -------------------------------------------------------------
        $subjectsList = [
            ['code' => 'QH', 'name' => "Al-Qur'an Hadis", 'category' => 'agama'],
            ['code' => 'AA', 'name' => 'Akidah Akhlak', 'category' => 'agama'],
            ['code' => 'FKH', 'name' => 'Fikih', 'category' => 'agama'],
            ['code' => 'SKI', 'name' => 'Sejarah Kebudayaan Islam', 'category' => 'agama'],
            ['code' => 'BAR', 'name' => 'Bahasa Arab', 'category' => 'agama'],
            ['code' => 'MTK', 'name' => 'Matematika', 'category' => 'umum'],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'category' => 'umum'],
            ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam (IPAS)', 'category' => 'umum'],
            ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial', 'category' => 'umum'],
            ['code' => 'PKN', 'name' => 'Pendidikan Pancasila', 'category' => 'umum'],
            ['code' => 'SBDP', 'name' => 'Seni Budaya & Prakarya', 'category' => 'umum'],
            ['code' => 'PJOK', 'name' => 'Pendidikan Jasmani & Olahraga', 'category' => 'umum'],
        ];

        $subjects = [];
        foreach ($subjectsList as $s) {
            $subjects[$s['code']] = Subject::firstOrCreate(
                ['code' => $s['code']],
                ['name' => $s['name'], 'category' => $s['category'], 'is_active' => true]
            );
        }

        // -------------------------------------------------------------
        // 5. KEBIJAKAN PENILAIAN GLOBAL (Grading Policy & KKM 70.00)
        // -------------------------------------------------------------
        $gradingPolicy = GradingPolicy::firstOrCreate(
            ['revision' => 1],
            [
                'kkm' => 70.00,
                'status' => 'active',
                'created_by' => $masterAdmin->id,
                'activated_by' => $masterAdmin->id,
                'activated_at' => $now,
            ]
        );

        // Ensure Scoring Components are mapped with 100% weights
        $components = ScoringComponent::all()->keyBy('code');
        $weights = [
            'assignment' => 20.00,
            'daily_test' => 20.00,
            'midterm' => 25.00,
            'final' => 25.00,
            'attendance' => 10.00,
        ];

        foreach ($weights as $code => $weight) {
            if (isset($components[$code])) {
                GradingPolicyComponent::firstOrCreate(
                    [
                        'grading_policy_id' => $gradingPolicy->id,
                        'scoring_component_id' => $components[$code]->id,
                    ],
                    ['weight_percentage' => $weight]
                );
            }
        }

        // -------------------------------------------------------------
        // 6. ROMBEL / KELAS (1 s/d 6 A & B)
        // -------------------------------------------------------------
        // Rombel Lampau (2025/2026 Genap)
        $pastClassrooms = [];
        for ($grade = 1; $grade <= 6; $grade++) {
            foreach (['A', 'B'] as $sec) {
                $cls = Classroom::firstOrCreate(
                    ['term_id' => $termPastGenap->id, 'grade_level' => $grade, 'section' => $sec],
                    ['roster_version' => 1]
                );
                $pastClassrooms["{$grade}-{$sec}"] = $cls;
            }
        }

        // Rombel Aktif (2026/2027 Ganjil)
        $activeClassrooms = [];
        for ($grade = 1; $grade <= 6; $grade++) {
            foreach (['A', 'B'] as $sec) {
                $cls = Classroom::firstOrCreate(
                    ['term_id' => $termActiveGanjil->id, 'grade_level' => $grade, 'section' => $sec],
                    ['roster_version' => 1]
                );
                $activeClassrooms["{$grade}-{$sec}"] = $cls;

                // Homeroom assignment (guru.ridwan is excluded so he remains purely Guru Mengajar)
                $teacherIndex = ($grade - 1) % count($homeroomTeachers);
                HomeroomAssignment::firstOrCreate(
                    ['classroom_id' => $cls->id, 'teacher_id' => $homeroomTeachers[$teacherIndex]->id, 'status' => 'active'],
                    ['assigned_by' => $masterAdmin->id]
                );

                // Class subjects
                foreach ($subjects as $code => $subj) {
                    $classSubject = ClassSubject::firstOrCreate([
                        'classroom_id' => $cls->id,
                        'subject_id' => $subj->id,
                    ]);

                    // Assign teacher
                    $tIndex = ($grade + ord($code[0])) % count($teachers);
                    TeachingAssignment::firstOrCreate(
                        ['class_subject_id' => $classSubject->id, 'teacher_id' => $teachers[$tIndex]->id, 'status' => 'assigned'],
                        ['starts_on' => '2026-07-13', 'assigned_by' => $academicAdmin->id]
                    );

                    // Create dummy schedule slot
                    ScheduleSlot::firstOrCreate(
                        ['class_subject_id' => $classSubject->id, 'weekday' => ($grade % 5) + 1],
                        ['starts_at' => '07:30:00', 'ends_at' => '09:00:00']
                    );
                }
            }
        }

        // -------------------------------------------------------------
        // 7. SISWA & WALI SINTETIS (48 Siswa)
        // -------------------------------------------------------------
        $studentNames = [
            ['name' => 'Muhammad Rayhan Al-Fatih', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Aisyah Putri Azzahra', 'gender' => 'P', 'place' => 'Cimahi'],
            ['name' => 'Ahmad Bilal Ramadhan', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Fatima Az-Zahra Al-Habsyi', 'gender' => 'P', 'place' => 'Garut'],
            ['name' => 'Umar Faruq Al-Ghazi', 'gender' => 'L', 'place' => 'Tasikmalaya'],
            ['name' => 'Khadijah Maryam Qanita', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Ali Zainal Abidin', 'gender' => 'L', 'place' => 'Sumedang'],
            ['name' => 'Hafshah Syahidah', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Ibrahim Khalilurrahman', 'gender' => 'L', 'place' => 'Subang'],
            ['name' => 'Zainab Humaira', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Hamzah Asadullah', 'gender' => 'L', 'place' => 'Cianjur'],
            ['name' => 'Ruqayyah Thoyyibah', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Yusuf Maulana Al-Hadi', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Safiyyah Nuha', 'gender' => 'P', 'place' => 'Purwakarta'],
            ['name' => 'Tariq Ziyad Al-Farisi', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Asma binti Abu Bakar', 'gender' => 'P', 'place' => 'Cimahi'],
            ['name' => 'Salim Maula Abi Hudzaifah', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Juwayriyah Salma', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Usamah bin Zayd', 'gender' => 'L', 'place' => 'Garut'],
            ['name' => 'Maimunah Hasna', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Saad bin Abi Waqqas', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Ummu Salamah', 'gender' => 'P', 'place' => 'Cimahi'],
            ['name' => 'Zubair bin Awwam', 'gender' => 'L', 'place' => 'Tasikmalaya'],
            ['name' => 'Atikah binti Zaid', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Talhah bin Ubaidillah', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Ummu Habibah', 'gender' => 'P', 'place' => 'Subang'],
            ['name' => 'Abu Ubaidah bin Jarrah', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Shafiyyah binti Abdil Muthallib', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Abdurrahman bin Auf', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Fathimah binti Asad', 'gender' => 'P', 'place' => 'Cianjur'],
            ['name' => 'Salman Al-Farisi', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Lubabah binti Harits', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Khalid bin Walid', 'gender' => 'L', 'place' => 'Garut'],
            ['name' => 'Khawlah binti Azwar', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Amr bin Ash', 'gender' => 'L', 'place' => 'Cimahi'],
            ['name' => 'Nusaibah binti Kaab', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Muadz bin Jabal', 'gender' => 'L', 'place' => 'Sumedang'],
            ['name' => 'Syifa binti Abdullah', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Abdullah bin Masud', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Arwa binti Kuraiz', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Jabir bin Abdullah', 'gender' => 'L', 'place' => 'Purwakarta'],
            ['name' => 'Halimah As-Sadiyah', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Ubay bin Kaab', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Barakah Ummu Ayman', 'gender' => 'P', 'place' => 'Bandung'],
            ['name' => 'Hasan bin Ali', 'gender' => 'L', 'place' => 'Garut'],
            ['name' => 'Husain bin Ali', 'gender' => 'L', 'place' => 'Bandung'],
            ['name' => 'Zainab binti Ali', 'gender' => 'P', 'place' => 'Cimahi'],
            ['name' => 'Fathimah binti Hizam', 'gender' => 'P', 'place' => 'Bandung'],
        ];

        $students = [];
        $activeEnrollments = [];
        $pastEnrollments = [];

        $classList = array_values($activeClassrooms);
        $pastClassList = array_values($pastClassrooms);

        foreach ($studentNames as $idx => $st) {
            $nis = sprintf('2025%04d', $idx + 1);
            $birthYear = 2014 + ($idx % 6);
            $birthDate = sprintf('%04d-%02d-%02d', $birthYear, ($idx % 12) + 1, ($idx % 27) + 1);

            $student = Student::firstOrCreate(
                ['nis' => $nis],
                [
                    'name' => $st['name'],
                    'gender' => $st['gender'],
                    'birth_date' => $birthDate,
                    'birth_place' => $st['place'],
                    'status' => 'active',
                ]
            );
            $students[] = $student;

            // Link Guardian (Q6.1) with realistic, individual family relationships:
            // - wali.hasan (Hasan Basri, S.T. - Ayah) is father to Rayhan (idx 0) and Aisyah (idx 1)
            // - wali.dewi (Dewi Sartika, S.E. - Ibu) is mother to Bilal (idx 2)
            // - wali.nurul (Nurul Hidayah, S.Sos. - Ibu) is mother to Fatima (idx 3)
            // - wali.agus (Agus Setiawan - Ayah) is father to Umar (idx 4)
            // - wali.hendra (Hendra Wijaya - Ayah) is father to Khadijah (idx 5)
            // - wali.rina (Rina Marlina - Ibu) is mother to Ali (idx 6)
            // - Remaining students each receive their own unique guardian account so no demo guardian gets bulk unrelated children.
            if ($idx === 0 || $idx === 1) {
                $guardianUser = $users['wali.hasan'];
                $relationship = 'ayah';
            } elseif ($idx === 2) {
                $guardianUser = $users['wali.dewi'];
                $relationship = 'ibu';
            } elseif ($idx === 3) {
                $guardianUser = $users['wali.nurul'];
                $relationship = 'ibu';
            } elseif ($idx === 4) {
                $guardianUser = $users['wali.agus'];
                $relationship = 'ayah';
            } elseif ($idx === 5) {
                $guardianUser = $users['wali.hendra'];
                $relationship = 'ayah';
            } elseif ($idx === 6) {
                $guardianUser = $users['wali.rina'];
                $relationship = 'ibu';
            } else {
                $gUsername = sprintf('wali.siswa.%02d', $idx + 1);
                $isFather = ($idx % 2 === 0);
                $relationship = $isFather ? 'ayah' : 'ibu';
                $guardianUser = User::firstOrCreate(
                    ['username' => $gUsername],
                    [
                        'name' => ($isFather ? 'Bpk. ' : 'Ibu ').'Wali '.$st['name'],
                        'email' => sprintf('wali.siswa%02d@example.test', $idx + 1),
                        'password' => $password,
                        'is_active' => true,
                        'must_change_password' => false,
                    ]
                );
                if (! $guardianUser->hasRole(Role::GUARDIAN)) {
                    $guardianUser->assignRole(Role::GUARDIAN);
                }
            }

            StudentGuardian::firstOrCreate(
                ['student_id' => $student->id, 'guardian_user_id' => $guardianUser->id],
                ['relationship' => $relationship, 'is_primary' => true]
            );

            // Assign past enrollment (2025/2026 Genap)
            $pastClass = $pastClassList[$idx % count($pastClassList)];
            $pastEnroll = Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'term_id' => $termPastGenap->id],
                [
                    'classroom_id' => $pastClass->id,
                    'starts_on' => '2026-01-05',
                    'ends_on' => '2026-06-20',
                    'status' => 'completed',
                ]
            );
            $pastEnrollments[] = $pastEnroll;

            // Assign active enrollment (2026/2027 Ganjil)
            $activeClass = $classList[$idx % count($classList)];
            $activeEnroll = Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'term_id' => $termActiveGanjil->id],
                [
                    'classroom_id' => $activeClass->id,
                    'starts_on' => '2026-07-13',
                    'status' => 'active',
                ]
            );
            $activeEnrollments[] = $activeEnroll;
        }

        // -------------------------------------------------------------
        // 8. AKTIVITAS PEMBELAJARAN (Absensi & Penilaian di Term Aktif)
        // -------------------------------------------------------------
        // Take a few active class subjects (e.g. Kelas 1-A, 2-A)
        $sampleClass = $activeClassrooms['1-A'];
        $sampleClassSubjects = ClassSubject::where('classroom_id', $sampleClass->id)->take(3)->get();
        $sampleEnrollments = Enrollment::where('classroom_id', $sampleClass->id)->get();

        foreach ($sampleClassSubjects as $cs) {
            // Sesi Absensi
            for ($meeting = 1; $meeting <= 3; $meeting++) {
                $session = AttendanceSession::firstOrCreate(
                    ['class_subject_id' => $cs->id, 'meeting_number' => $meeting],
                    [
                        'created_by' => $masterAdmin->id,
                        'held_on' => Carbon::parse('2026-08-01')->addWeeks($meeting)->toDateString(),
                        'topic' => "Pertemuan ke-{$meeting}: Pengenalan Materi Dasar",
                        'status' => 'finalized',
                        'finalized_at' => $now,
                        'finalized_by' => $masterAdmin->id,
                        'version' => 1,
                    ]
                );

                foreach ($sampleEnrollments as $enr) {
                    AttendanceRecord::firstOrCreate(
                        ['attendance_session_id' => $session->id, 'enrollment_id' => $enr->id],
                        ['status' => ($enr->id % 7 === 0) ? 'sick' : 'present', 'recorded_by' => $masterAdmin->id]
                    );
                }
            }

            // Penilaian (Assessments)
            $assessment1 = Assessment::firstOrCreate(
                ['class_subject_id' => $cs->id, 'title' => 'Tugas 1: Pemahaman Konsep'],
                [
                    'created_by' => $masterAdmin->id,
                    'scoring_component_id' => $components['assignment']->id,
                    'assessed_on' => '2026-08-15',
                    'status' => 'published',
                    'published_at' => $now,
                    'published_by' => $masterAdmin->id,
                    'version' => 1,
                ]
            );

            $assessment2 = Assessment::firstOrCreate(
                ['class_subject_id' => $cs->id, 'title' => 'Ulangan Harian 1'],
                [
                    'created_by' => $masterAdmin->id,
                    'scoring_component_id' => $components['daily_test']->id,
                    'assessed_on' => '2026-09-10',
                    'status' => 'published',
                    'published_at' => $now,
                    'published_by' => $masterAdmin->id,
                    'version' => 1,
                ]
            );

            foreach ($sampleEnrollments as $enr) {
                Score::firstOrCreate(
                    ['assessment_id' => $assessment1->id, 'enrollment_id' => $enr->id],
                    ['score' => 85.00, 'graded_by' => $masterAdmin->id, 'feedback' => 'Sangat baik dan teliti.']
                );
                Score::firstOrCreate(
                    ['assessment_id' => $assessment2->id, 'enrollment_id' => $enr->id],
                    ['score' => 78.50, 'graded_by' => $masterAdmin->id, 'feedback' => 'Tuntas KKM.']
                );
            }
        }

        // -------------------------------------------------------------
        // 9. RAPOR RESMI SEMESTER LAMPAU (2025/2026 Genap) & PDF
        // -------------------------------------------------------------
        $pdfContent = "%PDF-1.4\n"
            ."1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/MediaBox[0 0 595 842]/Parent 2 0 R>>endobj\n"
            ."xref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000106 00000 n \n"
            ."trailer<</Size 4/Root 1 0 R>>\nstartxref\n185\n%%EOF";

        $samplePastEnrollments = array_slice($pastEnrollments, 0, 15);
        $publishedReportCards = [];

        foreach ($samplePastEnrollments as $pEnr) {
            $pdfPath = "reports/report_card_demo_enr_{$pEnr->id}_rev_1.pdf";
            Storage::disk('local')->put($pdfPath, $pdfContent);

            $rc = ReportCard::firstOrCreate(
                ['enrollment_id' => $pEnr->id, 'revision' => 1],
                [
                    'term_id' => $termPastGenap->id,
                    'grading_policy_id' => $gradingPolicy->id,
                    'status' => 'published',
                    'availability' => 'current',
                    'version' => 1,
                    'source_version' => 'src_'.hash('sha256', "demo_source_payload_{$pEnr->id}"),
                    'homeroom_note' => 'Pertahankan prestasi belajar dan keaktifan beribadah di kelas berikutnya.',
                    'calculation_version' => '1.0',
                    'generated_at' => $now->copy()->subMonths(3),
                    'finalized_at' => $now->copy()->subMonths(3),
                    'finalized_by' => $masterAdmin->id,
                    'published_at' => $now->copy()->subMonths(3),
                    'published_by' => $masterAdmin->id,
                    'private_file_path' => $pdfPath,
                    'attendance_summary' => [
                        'present' => 90,
                        'sick' => 2,
                        'excused' => 1,
                        'absent' => 0,
                    ],
                    'identity_snapshot' => [
                        'student_name' => $pEnr->student->name,
                        'nis' => $pEnr->student->nis,
                        'classroom' => "{$pEnr->classroom->grade_level}-{$pEnr->classroom->section}",
                    ],
                ]
            );

            // Report card items snapshot
            $pClassSubjects = ClassSubject::where('classroom_id', $pEnr->classroom_id)->take(4)->get();
            foreach ($pClassSubjects as $cs) {
                ReportCardItem::firstOrCreate(
                    ['report_card_id' => $rc->id, 'class_subject_id' => $cs->id],
                    [
                        'subject_snapshot' => ['code' => $cs->subject->code, 'name' => $cs->subject->name],
                        'component_breakdown_snapshot' => [
                            'assignment' => 84.00,
                            'daily_test' => 80.00,
                            'midterm' => 82.00,
                            'final' => 85.00,
                            'attendance' => 100.00,
                        ],
                        'kkm_snapshot' => 70.00,
                        'final_score' => 84.50,
                        'kkm_status' => 'passed',
                    ]
                );
            }

            $publishedReportCards[] = $rc;
        }

        // Contoh Rapor Revisi (Revision Draft) untuk 1 siswa
        if (! empty($publishedReportCards)) {
            $baseRc = $publishedReportCards[0];
            ReportCard::firstOrCreate(
                ['enrollment_id' => $baseRc->enrollment_id, 'revision' => 2],
                [
                    'term_id' => $termPastGenap->id,
                    'grading_policy_id' => $gradingPolicy->id,
                    'supersedes_id' => $baseRc->id,
                    'status' => 'draft',
                    'availability' => 'needs_revision',
                    'version' => 1,
                    'homeroom_note' => 'Catatan revisi perbaikan nilai kehadiran.',
                    'calculation_version' => '1.0',
                    'lifecycle_reason' => 'Permohonan koreksi nilai dari wali kelas.',
                ]
            );
        }

        // -------------------------------------------------------------
        // 10. USULAN KENAIKAN & KELULUSAN SISWA (Student Progressions)
        // -------------------------------------------------------------
        if (count($publishedReportCards) >= 8) {
            // 1. Applied Promotion (1-A -> 2-A)
            StudentProgression::firstOrCreate(
                ['student_id' => $publishedReportCards[0]->enrollment->student_id, 'source_academic_year_id' => $yearPast->id],
                [
                    'source_report_card_id' => $publishedReportCards[0]->id,
                    'type' => ProgressionType::PROMOTION,
                    'target_academic_year_id' => $yearActive->id,
                    'target_classroom_id' => $activeClassrooms['2-A']->id,
                    'status' => ProgressionStatus::APPLIED,
                    'version' => 2,
                    'proposed_by' => $academicAdmin->id,
                    'approved_by' => $principal->id,
                    'approved_at' => $now->copy()->subMonths(2),
                    'applied_by' => $academicAdmin->id,
                    'applied_at' => $now->copy()->subMonths(2),
                    'reason' => 'Tuntas seluruh kompetensi dasar kelas 1.',
                ]
            );

            // 2. Approved Promotion (2-A -> 3-A)
            StudentProgression::firstOrCreate(
                ['student_id' => $publishedReportCards[1]->enrollment->student_id, 'source_academic_year_id' => $yearPast->id],
                [
                    'source_report_card_id' => $publishedReportCards[1]->id,
                    'type' => ProgressionType::PROMOTION,
                    'target_academic_year_id' => $yearActive->id,
                    'target_classroom_id' => $activeClassrooms['3-A']->id,
                    'status' => ProgressionStatus::APPROVED,
                    'version' => 1,
                    'proposed_by' => $academicAdmin->id,
                    'approved_by' => $principal->id,
                    'approved_at' => $now->copy()->subDays(10),
                    'reason' => 'Nilai rapor memenuhi KKM dan kehadiran memuaskan.',
                ]
            );

            // 3. Submitted Promotion (3-A -> 4-A)
            StudentProgression::firstOrCreate(
                ['student_id' => $publishedReportCards[2]->enrollment->student_id, 'source_academic_year_id' => $yearPast->id],
                [
                    'source_report_card_id' => $publishedReportCards[2]->id,
                    'type' => ProgressionType::PROMOTION,
                    'target_academic_year_id' => $yearActive->id,
                    'target_classroom_id' => $activeClassrooms['4-A']->id,
                    'status' => ProgressionStatus::SUBMITTED,
                    'version' => 1,
                    'proposed_by' => $academicAdmin->id,
                    'reason' => 'Usulan kenaikan kelas dari wali kelas 3-A.',
                ]
            );

            // 4. Draft Promotion (4-A -> 5-A)
            StudentProgression::firstOrCreate(
                ['student_id' => $publishedReportCards[3]->enrollment->student_id, 'source_academic_year_id' => $yearPast->id],
                [
                    'source_report_card_id' => $publishedReportCards[3]->id,
                    'type' => ProgressionType::PROMOTION,
                    'target_academic_year_id' => $yearActive->id,
                    'target_classroom_id' => $activeClassrooms['5-A']->id,
                    'status' => ProgressionStatus::DRAFT,
                    'version' => 1,
                    'proposed_by' => $academicAdmin->id,
                    'reason' => 'Draf penyusunan kenaikan rombel 4-A.',
                ]
            );

            // 5. Needs Review Promotion (5-A -> 6-A)
            StudentProgression::firstOrCreate(
                ['student_id' => $publishedReportCards[4]->enrollment->student_id, 'source_academic_year_id' => $yearPast->id],
                [
                    'source_report_card_id' => $publishedReportCards[4]->id,
                    'type' => ProgressionType::PROMOTION,
                    'target_academic_year_id' => $yearActive->id,
                    'target_classroom_id' => $activeClassrooms['6-A']->id,
                    'status' => ProgressionStatus::NEEDS_REVIEW,
                    'version' => 1,
                    'proposed_by' => $academicAdmin->id,
                    'reason' => 'Perubahan nilai rapor terdeteksi setelah draf diajukan.',
                ]
            );

            // 6. Rejected Promotion (1-B -> 2-B)
            StudentProgression::firstOrCreate(
                ['student_id' => $publishedReportCards[5]->enrollment->student_id, 'source_academic_year_id' => $yearPast->id],
                [
                    'source_report_card_id' => $publishedReportCards[5]->id,
                    'type' => ProgressionType::PROMOTION,
                    'target_academic_year_id' => $yearActive->id,
                    'target_classroom_id' => $activeClassrooms['2-B']->id,
                    'status' => ProgressionStatus::REJECTED,
                    'version' => 1,
                    'proposed_by' => $academicAdmin->id,
                    'approved_by' => $principal->id,
                    'rejection_reason' => 'Nilai capaian kompetensi dasar belum memenuhi batas minimum KKM sekolah.',
                ]
            );

            // 7. Applied Graduation (Kelas 6)
            StudentProgression::firstOrCreate(
                ['student_id' => $publishedReportCards[6]->enrollment->student_id, 'source_academic_year_id' => $yearPast->id],
                [
                    'source_report_card_id' => $publishedReportCards[6]->id,
                    'type' => ProgressionType::GRADUATION,
                    'status' => ProgressionStatus::APPLIED,
                    'version' => 2,
                    'proposed_by' => $academicAdmin->id,
                    'approved_by' => $principal->id,
                    'approved_at' => $now->copy()->subMonths(2),
                    'applied_by' => $academicAdmin->id,
                    'applied_at' => $now->copy()->subMonths(2),
                    'reason' => 'Lulus jenjang Madrasah Ibtidaiyah dengan predikat sangat baik.',
                ]
            );

            // 8. Approved Retention (Tinggal Kelas)
            StudentProgression::firstOrCreate(
                ['student_id' => $publishedReportCards[7]->enrollment->student_id, 'source_academic_year_id' => $yearPast->id],
                [
                    'source_report_card_id' => $publishedReportCards[7]->id,
                    'type' => ProgressionType::RETENTION,
                    'target_academic_year_id' => $yearActive->id,
                    'target_classroom_id' => $activeClassrooms['2-A']->id,
                    'status' => ProgressionStatus::APPROVED,
                    'version' => 1,
                    'proposed_by' => $academicAdmin->id,
                    'approved_by' => $principal->id,
                    'approved_at' => $now->copy()->subDays(5),
                    'reason' => 'Rekomendasi dewan guru untuk pemantapan kompetensi di tingkat yang sama.',
                ]
            );
        }

        // -------------------------------------------------------------
        // 11. RIWAYAT AUDIT SISTEM (Audit Trail Entries)
        // -------------------------------------------------------------
        $auditSamples = [
            [
                'actor_user_id' => $masterAdmin->id,
                'action' => 'INITIALIZE_SETTINGS',
                'entity_type' => 'App\Models\SystemSetting',
                'entity_id' => '1',
                'scope' => AuditScope::SYSTEM,
                'allowed_changes' => ['school_name' => 'Madrasah Ibtidaiyah Alghivari', 'timezone' => 'Asia/Jakarta'],
                'reason' => 'Inisialisasi parameter operasional sistem',
            ],
            [
                'actor_user_id' => $masterAdmin->id,
                'action' => 'ACTIVATE_GRADING_POLICY',
                'entity_type' => 'App\Models\GradingPolicy',
                'entity_id' => (string) $gradingPolicy->id,
                'scope' => AuditScope::SYSTEM,
                'allowed_changes' => ['kkm' => 70.00, 'status' => 'active', 'weights' => $weights],
                'reason' => 'Aktivasi kebijakan penilaian terpadu 100%',
            ],
            [
                'actor_user_id' => $academicAdmin->id,
                'action' => 'PUBLISH_SEMESTER_REPORTS',
                'entity_type' => 'App\Models\ReportCard',
                'entity_id' => '1',
                'scope' => AuditScope::REPORT,
                'allowed_changes' => ['status' => 'published', 'availability' => 'current'],
                'reason' => 'Publikasi resmi rapor semester genap 2025/2026',
            ],
            [
                'actor_user_id' => $principal->id,
                'action' => 'APPROVE_STUDENT_PROGRESSION',
                'entity_type' => 'App\Models\StudentProgression',
                'entity_id' => '2',
                'scope' => AuditScope::PROGRESSION,
                'allowed_changes' => ['status' => 'approved', 'approved_by' => $principal->id],
                'reason' => 'Persetujuan resmi kenaikan siswa ke kelas 3-A',
            ],
            [
                'actor_user_id' => $academicAdmin->id,
                'action' => 'APPLY_STUDENT_PROGRESSION',
                'entity_type' => 'App\Models\StudentProgression',
                'entity_id' => '1',
                'scope' => AuditScope::PROGRESSION,
                'allowed_changes' => ['status' => 'applied', 'new_enrollment_created' => true],
                'reason' => 'Eksekusi mutasi akademik kenaikan kelas',
            ],
        ];

        foreach ($auditSamples as $audit) {
            AuditLog::create([
                'actor_user_id' => $audit['actor_user_id'],
                'action' => $audit['action'],
                'entity_type' => $audit['entity_type'],
                'entity_id' => $audit['entity_id'],
                'scope' => $audit['scope'],
                'event_visibility' => AuditVisibility::INTERNAL,
                'allowed_changes' => $audit['allowed_changes'],
                'reason' => $audit['reason'],
                'created_at' => $now->copy()->subDays(random_int(1, 30)),
            ]);
        }

        // -------------------------------------------------------------
        // 12. NOTIFIKASI INBOX PENGGUNA (Persistent Notifications)
        // -------------------------------------------------------------
        $notificationsData = [
            [
                'recipient_id' => $principal->id,
                'event_category' => 'progression',
                'sanitized_summary' => 'Staf akademik telah mengajukan usulan kenaikan kelas rombel 3-A untuk ditinjau.',
                'scope_type' => 'progression',
                'scope_id' => '3',
            ],
            [
                'recipient_id' => $academicAdmin->id,
                'event_category' => 'progression',
                'sanitized_summary' => 'Kepala Madrasah telah menyetujui usulan kenaikan siswa Muhammad Rayhan ke kelas 3-A.',
                'scope_type' => 'progression',
                'scope_id' => '2',
            ],
            [
                'recipient_id' => $guardians[0]->id,
                'event_category' => 'report_card',
                'sanitized_summary' => 'Rapor resmi semester genap ananda telah dipublikasikan dan siap ditinjau.',
                'scope_type' => 'report_card',
                'scope_id' => '1',
            ],
        ];

        foreach ($notificationsData as $n) {
            Notification::create([
                'id' => (string) Str::uuid(),
                'recipient_id' => $n['recipient_id'],
                'event_category' => $n['event_category'],
                'sanitized_summary' => $n['sanitized_summary'],
                'scope_type' => $n['scope_type'],
                'scope_id' => $n['scope_id'],
                'visible_item_count' => 1,
                'read_at' => null,
                'created_at' => $now,
            ]);
        }

        $this->command?->info('Berhasil menyemai data portofolio sintetis realistis MI Alghivari!');
    }
}
