<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display students listing.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Student::class);

        $user = $request->user();
        $canReadBiodata = $user->can('export', Student::class);
        $canManage = $user->can('create', Student::class);
        $query = Student::query()->latest();
        if ($canReadBiodata) {
            $query->with('guardians:id,name,username');
        } else {
            $query->whereHas('enrollments', fn ($enrollments) => $enrollments
                ->where('status', 'active')
                ->whereHas('classroom', fn ($classrooms) => $classrooms
                    ->whereHas('activeHomeroom', fn ($assignments) => $assignments->where('teacher_id', $user->id))
                    ->orWhereHas('classSubjects.teachingAssignments', fn ($assignments) => $assignments->active()->where('teacher_id', $user->id))));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $students = $query->paginate(15)->through(fn (Student $s) => [
            'id' => $s->id,
            'nis' => $s->nis,
            'name' => $s->name,
            'gender' => $s->gender,
            ...($canReadBiodata ? [
                'birth_date' => $s->birth_date?->format('Y-m-d'),
                'birth_place' => $s->birth_place,
            ] : []),
            'status' => $s->status,
            'guardians' => $canReadBiodata ? $s->guardians->map(fn (User $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'username' => $g->username,
                'relationship' => $g->pivot?->relationship,
                'is_primary' => (bool) $g->pivot?->is_primary,
            ]) : [],
        ]);

        // Available guardians (accounts with GUARDIAN role created by Master Admin per Q6.1)
        $availableGuardians = $canManage ? User::whereHas('userRoles', fn ($q) => $q->where('role', Role::GUARDIAN->value))
            ->where('is_active', true)
            ->select('id', 'name', 'username')
            ->orderBy('name')
            ->get() : [];

        return Inertia::render('Admin/Academic/Students/Index', [
            'students' => $students,
            'availableGuardians' => $availableGuardians,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Store a newly created student and optional guardian linkage (Q6.1).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:32', 'unique:students,nis'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:L,P'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:64'],
            'guardian_user_id' => ['nullable', 'exists:users,id'],
            'guardian_relationship' => ['nullable', 'string', 'in:ayah,ibu,wali'],
        ]);

        // Validate that guardian has GUARDIAN role
        if (! empty($validated['guardian_user_id'])) {
            $guardianUser = User::findOrFail($validated['guardian_user_id']);
            if (! $guardianUser->hasRole(Role::GUARDIAN)) {
                return back()->withErrors(['guardian_user_id' => 'Pengguna yang dipilih tidak memiliki peran Wali Santri/Murid.']);
            }
        }

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($validated) {
                $student = Student::create([
                    'nis' => $validated['nis'],
                    'name' => $validated['name'],
                    'gender' => $validated['gender'],
                    'birth_date' => $validated['birth_date'] ?? null,
                    'birth_place' => $validated['birth_place'] ?? null,
                    'status' => 'active',
                ]);

                if (! empty($validated['guardian_user_id'])) {
                    $student->guardians()->attach($validated['guardian_user_id'], [
                        'relationship' => $validated['guardian_relationship'] ?? 'wali',
                        'is_primary' => true,
                    ]);
                }

                return $student;
            },
            auditFactory: fn (Student $student) => [
                'actor_id' => $actor?->id,
                'action' => 'CREATE_STUDENT',
                'entity_type' => Student::class,
                'entity_id' => $student->id,
                'scope' => AuditScope::STUDENT,
                'changes' => [
                    'nis' => $student->nis,
                    'name' => $student->name,
                    'guardian_id' => $validated['guardian_user_id'] ?? null,
                ],
                'reason' => "Biodata siswa baru {$student->name} ({$student->nis}) didaftarkan",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

    /**
     * Update student biodata.
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:L,P'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string', 'in:active,graduated,transferred,inactive'],
        ]);

        if ($validated['status'] !== $student->status) {
            if (in_array('graduated', [$validated['status'], $student->status], true)) {
                throw ValidationException::withMessages(['status' => 'Perubahan status kelulusan harus melalui keputusan kenaikan/kelulusan.']);
            }

            if (! $request->user()->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN])) {
                throw ValidationException::withMessages(['status' => 'Petugas Data Entry hanya dapat memperbarui biodata, tanpa mengubah status siswa.']);
            }
        }

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($student, $validated) {
                $student->update($validated);

                return $student;
            },
            auditFactory: fn (Student $updated) => [
                'actor_id' => $actor?->id,
                'action' => 'UPDATE_STUDENT',
                'entity_type' => Student::class,
                'entity_id' => $updated->id,
                'scope' => AuditScope::STUDENT,
                'changes' => $validated,
                'reason' => "Biodata siswa {$updated->nis} diperbarui",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Link or unlink student to a guardian account (Q6.1).
     */
    public function linkGuardian(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'guardian_user_id' => ['required', 'exists:users,id'],
            'relationship' => ['required', 'string', 'in:ayah,ibu,wali'],
            'is_primary' => ['boolean'],
        ]);

        $guardianUser = User::findOrFail($validated['guardian_user_id']);
        if (! $guardianUser->hasRole(Role::GUARDIAN)) {
            return back()->withErrors(['guardian_user_id' => 'Pengguna yang dipilih tidak memiliki peran Wali Santri/Murid.']);
        }

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($student, $validated) {
                $student->guardians()->syncWithoutDetaching([
                    $validated['guardian_user_id'] => [
                        'relationship' => $validated['relationship'],
                        'is_primary' => $validated['is_primary'] ?? true,
                    ],
                ]);

                return $student;
            },
            auditFactory: fn (Student $s) => [
                'actor_id' => $actor?->id,
                'action' => 'LINK_STUDENT_GUARDIAN',
                'entity_type' => Student::class,
                'entity_id' => $s->id,
                'scope' => AuditScope::STUDENT,
                'changes' => $validated,
                'reason' => "Hubungan wali santri dikaitkan pada siswa {$s->name}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Hubungan wali siswa berhasil ditautkan.');
    }

    /**
     * Export all students data to CSV stream.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Student::class);

        $filename = 'daftar_siswa_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['nis', 'name', 'gender', 'birth_place', 'birth_date', 'status']);

            Student::orderBy('nis')->chunk(200, function ($students) use ($handle) {
                foreach ($students as $student) {
                    fputcsv($handle, [
                        $student->nis,
                        $student->name,
                        $student->gender,
                        $student->birth_place,
                        $student->birth_date?->format('Y-m-d'),
                        $student->status,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Download CSV template for student import.
     */
    public function template(): StreamedResponse
    {
        $this->authorize('create', Student::class);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_impor_siswa.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['nis', 'name', 'gender', 'birth_place', 'birth_date']);
            fputcsv($handle, ['1001', 'Ahmad Santoso', 'L', 'Bandung', '2015-05-12']);
            fputcsv($handle, ['1002', 'Siti Fatimah', 'P', 'Bandung', '2015-08-20']);
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Bulk import students from uploaded CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            throw ValidationException::withMessages(['file' => 'Gagal membaca file CSV.']);
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'File CSV kosong atau tidak memiliki baris tajuk.']);
        }

        $headerMap = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $requiredCols = ['nis', 'name', 'gender'];
        foreach ($requiredCols as $col) {
            if (! in_array($col, $headerMap, true)) {
                fclose($handle);
                throw ValidationException::withMessages([
                    'file' => "Format kolom tidak sesuai. Kolom '{$col}' wajib ada pada baris pertama.",
                ]);
            }
        }

        $nisIndex = array_search('nis', $headerMap, true);
        $nameIndex = array_search('name', $headerMap, true);
        $genderIndex = array_search('gender', $headerMap, true);
        $birthPlaceIndex = in_array('birth_place', $headerMap, true) ? array_search('birth_place', $headerMap, true) : false;
        $birthDateIndex = in_array('birth_date', $headerMap, true) ? array_search('birth_date', $headerMap, true) : false;

        $rowsToInsert = [];
        $fileNisSet = [];
        $lineNum = 1;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;
            if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $nis = trim((string) ($row[$nisIndex] ?? ''));
            $name = trim((string) ($row[$nameIndex] ?? ''));
            $gender = strtoupper(trim((string) ($row[$genderIndex] ?? '')));
            $birthPlace = $birthPlaceIndex !== false ? trim((string) ($row[$birthPlaceIndex] ?? '')) : null;
            $birthDate = $birthDateIndex !== false ? trim((string) ($row[$birthDateIndex] ?? '')) : null;

            if ($nis === '') {
                $errors[] = "Baris {$lineNum}: NIS tidak boleh kosong.";
            } elseif (strlen($nis) > 32) {
                $errors[] = "Baris {$lineNum}: NIS melebihi 32 karakter.";
            } elseif (isset($fileNisSet[$nis])) {
                $errors[] = "Baris {$lineNum}: NIS '{$nis}' terduplikasi di dalam file.";
            } else {
                $fileNisSet[$nis] = true;
            }

            if ($name === '') {
                $errors[] = "Baris {$lineNum}: Nama siswa tidak boleh kosong.";
            } elseif (strlen($name) > 255) {
                $errors[] = "Baris {$lineNum}: Nama siswa melebihi 255 karakter.";
            }

            if (! in_array($gender, ['L', 'P'], true)) {
                $errors[] = "Baris {$lineNum}: Jenis kelamin harus 'L' atau 'P'.";
            }

            if (! empty($birthDate)) {
                $d = \DateTime::createFromFormat('Y-m-d', $birthDate);
                if (! $d || $d->format('Y-m-d') !== $birthDate) {
                    $errors[] = "Baris {$lineNum}: Format tanggal lahir '{$birthDate}' tidak valid (gunakan format YYYY-MM-DD).";
                }
            } else {
                $birthDate = null;
            }

            $rowsToInsert[] = [
                'nis' => $nis,
                'name' => $name,
                'gender' => $gender,
                'birth_place' => $birthPlace ?: null,
                'birth_date' => $birthDate,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        fclose($handle);

        if (empty($rowsToInsert) && empty($errors)) {
            throw ValidationException::withMessages(['file' => 'Tidak ada baris data siswa yang ditemukan dalam file.']);
        }

        if (! empty($fileNisSet)) {
            $existingNis = Student::whereIn('nis', array_keys($fileNisSet))->pluck('nis')->all();
            if (! empty($existingNis)) {
                $errors[] = 'NIS sudah terdaftar di sistem: '.implode(', ', array_slice($existingNis, 0, 5)).(count($existingNis) > 5 ? ' dsb.' : '.');
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'file' => implode(' | ', array_slice($errors, 0, 10)).(count($errors) > 10 ? ' (dan kesalahan lainnya)' : ''),
            ]);
        }

        $actor = $request->user();
        $count = count($rowsToInsert);

        $this->auditService->executeAtomic(
            operation: function () use ($rowsToInsert) {
                Student::insert($rowsToInsert);

                return true;
            },
            auditFactory: fn () => [
                'actor_id' => $actor?->id,
                'action' => 'IMPORT_STUDENTS',
                'entity_type' => Student::class,
                'entity_id' => 0,
                'scope' => AuditScope::STUDENT,
                'changes' => [
                    'imported_count' => $count,
                ],
                'reason' => "Impor massal {$count} siswa melalui file CSV",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Berhasil mengimpor {$count} data siswa.");
    }
}
