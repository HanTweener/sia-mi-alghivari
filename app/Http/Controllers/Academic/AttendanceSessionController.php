<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\DataEntryDelegation;
use App\Models\User;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSessionController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display list of attendance sessions for a class subject.
     */
    public function index(Request $request, ClassSubject $classSubject): Response
    {
        $this->authorize('viewAny', [AttendanceSession::class, $classSubject]);

        $user = $request->user();

        $classSubject->load([
            'classroom.term.academicYear',
            'subject',
            'activeTeachingAssignment.teacher:id,name',
        ]);

        $sessions = $classSubject->attendanceSessions()
            ->with(['createdBy:id,name', 'finalizedBy:id,name'])
            ->withCount([
                'records as present_count' => fn ($q) => $q->where('status', AttendanceRecord::STATUS_PRESENT),
                'records as excused_count' => fn ($q) => $q->where('status', AttendanceRecord::STATUS_EXCUSED),
                'records as sick_count' => fn ($q) => $q->where('status', AttendanceRecord::STATUS_SICK),
                'records as absent_count' => fn ($q) => $q->where('status', AttendanceRecord::STATUS_ABSENT),
                'records as total_recorded' => fn ($q) => $q->whereNotNull('status'),
            ])
            ->orderBy('meeting_number', 'asc')
            ->get();

        $nextMeetingNumber = ((int) ($sessions->max('meeting_number') ?? 0)) + 1;

        $canManageDelegations = $user->can('manageDelegations', AttendanceSession::class);
        $delegations = [];
        $dataEntryUsers = [];

        if ($canManageDelegations) {
            $assignmentIds = $classSubject->teachingAssignments()->pluck('id');
            $delegations = DataEntryDelegation::whereIn('teaching_assignment_id', $assignmentIds)
                ->where('capability', 'attendance_entry')
                ->with(['user:id,name,username', 'grantedBy:id,name'])
                ->latest()
                ->get();

            $dataEntryUsers = User::whereHas('userRoles', fn ($q) => $q->where('role', Role::DATA_ENTRY_ADMIN->value))
                ->where('is_active', true)
                ->select('id', 'name', 'username')
                ->get();
        }

        return Inertia::render('Admin/Academic/Attendance/Index', [
            'classSubject' => $classSubject,
            'sessions' => $sessions,
            'nextMeetingNumber' => $nextMeetingNumber,
            'canCreate' => $user->can('create', [AttendanceSession::class, $classSubject]),
            'canManageDelegations' => $canManageDelegations,
            'delegations' => $delegations,
            'dataEntryUsers' => $dataEntryUsers,
        ]);
    }

    /**
     * Create a new draft attendance session.
     */
    public function store(Request $request, ClassSubject $classSubject): RedirectResponse
    {
        $this->authorize('create', [AttendanceSession::class, $classSubject]);

        $validated = $request->validate([
            'meeting_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('attendance_sessions', 'meeting_number')->where('class_subject_id', $classSubject->id),
            ],
            'held_on' => ['required', 'date'],
            'topic' => ['nullable', 'string', 'max:255'],
        ], [
            'meeting_number.unique' => 'Nomor pertemuan ini sudah digunakan pada mata pelajaran kelas ini.',
        ]);

        $user = $request->user();

        // Provenance: find assignment effective on held_on, or fallback to active assignment
        $targetDate = Carbon::parse($validated['held_on']);
        $assignment = $classSubject->teachingAssignments()
            ->where('status', 'assigned')
            ->where('starts_on', '<=', $targetDate)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>', $targetDate))
            ->first() ?? $classSubject->activeTeachingAssignment;

        $execution = $this->auditService->executeAtomic(
            operation: function () use ($classSubject, $assignment, $user, $validated) {
                return AttendanceSession::create([
                    'class_subject_id' => $classSubject->id,
                    'teaching_assignment_id' => $assignment?->id,
                    'created_by' => $user->id,
                    'meeting_number' => $validated['meeting_number'],
                    'held_on' => $validated['held_on'],
                    'topic' => $validated['topic'] ?? null,
                    'status' => AttendanceSession::STATUS_DRAFT,
                    'version' => 1,
                ]);
            },
            auditFactory: fn (AttendanceSession $session) => [
                'actor_id' => $user->id,
                'action' => 'CREATE_ATTENDANCE_SESSION',
                'entity_type' => 'attendance_session',
                'entity_id' => $session->id,
                'scope' => AuditScope::ATTENDANCE,
                'changes' => [
                    'class_subject_id' => $classSubject->id,
                    'meeting_number' => $validated['meeting_number'],
                    'held_on' => $validated['held_on'],
                    'topic' => $validated['topic'] ?? null,
                ],
                'reason' => 'Membuat sesi presensi pertemuan baru',
            ]
        );

        $session = $execution['result'];

        return redirect()->route('academic.attendance-sessions.show', $session->id)
            ->with('success', "Draf pertemuan ke-{$session->meeting_number} berhasil dibuat.");
    }

    /**
     * Display attendance sheet for a session.
     */
    public function show(Request $request, AttendanceSession $session): Response
    {
        $this->authorize('view', $session);

        $user = $request->user();

        $session->load([
            'classSubject.classroom.term.academicYear',
            'classSubject.subject',
            'classSubject.activeTeachingAssignment.teacher:id,name',
            'createdBy:id,name',
            'finalizedBy:id,name',
        ]);

        $classroom = $session->classSubject->classroom;

        // Eligible students on the session date
        $eligibleEnrollments = $classroom->enrollments()
            ->eligibleOn($session->held_on)
            ->with('student:id,nis,name,gender')
            ->get();

        $existingRecords = $session->records()->get()->keyBy('enrollment_id');

        $students = $eligibleEnrollments->map(function ($enrollment) use ($existingRecords) {
            $record = $existingRecords->get($enrollment->id);

            return [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student->id,
                'nis' => $enrollment->student->nis,
                'name' => $enrollment->student->name,
                'gender' => $enrollment->student->gender,
                'status' => $record?->status, // null means unrecorded (not absent!)
                'recorded_at' => $record?->updated_at?->toIso8601String(),
            ];
        });

        $totalEligible = $students->count();
        $presentCount = $students->where('status', AttendanceRecord::STATUS_PRESENT)->count();
        $excusedCount = $students->where('status', AttendanceRecord::STATUS_EXCUSED)->count();
        $sickCount = $students->where('status', AttendanceRecord::STATUS_SICK)->count();
        $absentCount = $students->where('status', AttendanceRecord::STATUS_ABSENT)->count();
        $unrecordedCount = $totalEligible - ($presentCount + $excusedCount + $sickCount + $absentCount);

        $isDataEntryOnly = $user->hasRole(Role::DATA_ENTRY_ADMIN) && ! $user->can('delete', $session);

        return Inertia::render('Admin/Academic/Attendance/Show', [
            'session' => $session,
            'classroom' => $classroom,
            'students' => $students,
            'stats' => [
                'total_eligible' => $totalEligible,
                'present_count' => $presentCount,
                'excused_count' => $excusedCount,
                'sick_count' => $sickCount,
                'absent_count' => $absentCount,
                'unrecorded_count' => $unrecordedCount,
            ],
            'permissions' => [
                'can_manage_draft' => $user->can('updateRecords', $session),
                'can_finalize' => $user->can('finalize', $session),
                'can_delete' => $user->can('delete', $session),
                'is_data_entry' => $isDataEntryOnly,
            ],
        ]);
    }

    /**
     * Update session metadata.
     */
    public function update(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorize('update', $session);

        if ($session->isFinalized()) {
            throw ValidationException::withMessages([
                'message' => 'Sesi kehadiran yang telah disahkan (finalized) tidak dapat diubah.',
            ]);
        }

        $validated = $request->validate([
            'expected_version' => ['required', 'integer'],
            'meeting_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('attendance_sessions', 'meeting_number')
                    ->where('class_subject_id', $session->class_subject_id)
                    ->ignore($session->id),
            ],
            'held_on' => ['required', 'date'],
            'topic' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['expected_version'] !== $session->version) {
            throw ValidationException::withMessages([
                'expected_version' => 'Sesi telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
            ]);
        }

        $this->auditService->executeAtomic(
            operation: function () use ($session, $validated) {
                $session->update([
                    'meeting_number' => $validated['meeting_number'],
                    'held_on' => $validated['held_on'],
                    'topic' => $validated['topic'] ?? null,
                    'version' => $session->version + 1,
                ]);

                return $session;
            },
            auditFactory: fn (AttendanceSession $session) => [
                'actor_id' => $request->user()->id,
                'action' => 'UPDATE_ATTENDANCE_SESSION',
                'entity_type' => 'attendance_session',
                'entity_id' => $session->id,
                'scope' => AuditScope::ATTENDANCE,
                'changes' => [
                    'meeting_number' => $validated['meeting_number'],
                    'held_on' => $validated['held_on'],
                    'topic' => $validated['topic'] ?? null,
                ],
                'reason' => 'Memperbarui metadata sesi presensi',
            ]
        );

        return back()->with('success', 'Metadata pertemuan berhasil diperbarui.');
    }

    /**
     * Save draft attendance records.
     */
    public function updateRecords(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorize('updateRecords', $session);

        if ($session->isFinalized()) {
            throw ValidationException::withMessages([
                'message' => 'Sesi kehadiran telah disahkan dan tidak dapat diubah lagi.',
            ]);
        }

        $validated = $request->validate([
            'expected_version' => ['required', 'integer'],
            'expected_roster_version' => ['required', 'integer'],
            'entries' => ['required', 'array'],
            'entries.*.enrollment_id' => ['required', 'integer'],
            'entries.*.status' => ['nullable', 'string', 'in:present,excused,sick,absent'],
        ]);

        if ($validated['expected_version'] !== $session->version) {
            throw ValidationException::withMessages([
                'expected_version' => 'Sesi telah diperbarui oleh pengguna lain. Harap muat ulang halaman.',
            ]);
        }

        $classroom = $session->classSubject->classroom;
        if ($validated['expected_roster_version'] !== $classroom->roster_version) {
            throw ValidationException::withMessages([
                'roster_changed' => "Daftar siswa pada kelas ini telah berubah (versi roster terbaru: {$classroom->roster_version}). Harap muat ulang halaman untuk meninjau peserta yang berlaku.",
            ]);
        }

        $user = $request->user();
        $isDataEntryOnly = $user->hasRole(Role::DATA_ENTRY_ADMIN) && ! $user->can('delete', $session);

        $existingRecords = $session->records()->get()->keyBy('enrollment_id');

        // Data Entry cannot clear existing records
        if ($isDataEntryOnly) {
            foreach ($validated['entries'] as $entry) {
                $status = $entry['status'] ?? null;
                $enrId = $entry['enrollment_id'];

                if (empty($status) && $existingRecords->has($enrId)) {
                    throw ValidationException::withMessages([
                        'entries' => 'Petugas Data Entry tidak memiliki izin untuk mengosongkan rekaman kehadiran yang sudah ada.',
                    ]);
                }
            }
        }

        // Active delegation ID for Data Entry provenance
        $delegationId = null;
        if ($isDataEntryOnly) {
            $delegation = DataEntryDelegation::where('user_id', $user->id)
                ->where('teaching_assignment_id', $session->classSubject->activeTeachingAssignment?->id)
                ->where('capability', 'attendance_entry')
                ->active()
                ->first();

            $delegationId = $delegation?->id;
        }

        // Valid eligible enrollments on session held_on
        $eligibleEnrollments = $classroom->enrollments()
            ->eligibleOn($session->held_on)
            ->pluck('id')
            ->flip();

        if (collect($validated['entries'])->pluck('enrollment_id')->diff($eligibleEnrollments->keys())->isNotEmpty()) {
            throw ValidationException::withMessages([
                'entries' => 'Sebagian siswa tidak terdaftar atau tidak aktif pada tanggal sesi ini.',
            ]);
        }

        $this->auditService->executeAtomic(
            operation: function () use ($session, $validated, $eligibleEnrollments, $existingRecords, $user, $delegationId, $isDataEntryOnly) {
                foreach ($validated['entries'] as $entry) {
                    $enrId = $entry['enrollment_id'];
                    $status = $entry['status'] ?? null;

                    if (! isset($eligibleEnrollments[$enrId])) {
                        continue;
                    }

                    if (empty($status)) {
                        if (! $isDataEntryOnly && $existingRecords->has($enrId)) {
                            $existingRecords->get($enrId)->delete();
                        }
                    } else {
                        AttendanceRecord::updateOrCreate(
                            [
                                'attendance_session_id' => $session->id,
                                'enrollment_id' => $enrId,
                            ],
                            [
                                'status' => $status,
                                'recorded_by' => $user->id,
                                'delegation_id' => $delegationId,
                            ]
                        );
                    }
                }

                $session->increment('version');

                return true;
            },
            auditFactory: fn () => [
                'actor_id' => $user->id,
                'action' => 'UPDATE_ATTENDANCE_RECORDS',
                'entity_type' => 'attendance_session',
                'entity_id' => $session->id,
                'scope' => AuditScope::ATTENDANCE,
                'changes' => [
                    'entries_count' => count($validated['entries']),
                    'delegation_id' => $delegationId,
                ],
                'reason' => 'Menyimpan draf rekaman presensi',
            ]
        );

        return back()->with('success', 'Draf rekaman kehadiran berhasil disimpan.');
    }

    /**
     * Finalize an attendance session into frozen state.
     */
    public function finalize(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorize('finalize', $session);

        if ($session->isFinalized()) {
            throw ValidationException::withMessages([
                'message' => 'Sesi kehadiran sudah disahkan sebelumnya.',
            ]);
        }

        $validated = $request->validate([
            'expected_version' => ['required', 'integer'],
            'expected_roster_version' => ['required', 'integer'],
        ]);

        if ($validated['expected_version'] !== $session->version) {
            throw ValidationException::withMessages([
                'expected_version' => 'Sesi telah diubah pengguna lain. Harap muat ulang halaman.',
            ]);
        }

        $classroom = $session->classSubject->classroom;
        if ($validated['expected_roster_version'] !== $classroom->roster_version) {
            throw ValidationException::withMessages([
                'roster_changed' => 'Daftar siswa pada kelas ini telah berubah. Harap tinjau ulang peserta sebelum mengesahkan.',
            ]);
        }

        $eligibleEnrollments = $classroom->enrollments()
            ->eligibleOn($session->held_on)
            ->pluck('id');

        $totalEligible = $eligibleEnrollments->count();

        if ($totalEligible === 0) {
            throw ValidationException::withMessages([
                'message' => 'Tidak ada siswa yang terdaftar aktif pada tanggal pertemuan ini.',
            ]);
        }

        $recordedCount = $session->records()
            ->whereIn('enrollment_id', $eligibleEnrollments)
            ->whereNotNull('status')
            ->count();

        if ($recordedCount < $totalEligible) {
            $unrecorded = $totalEligible - $recordedCount;
            throw ValidationException::withMessages([
                'message' => "Semua siswa yang berlaku ({$totalEligible} siswa) wajib memiliki status kehadiran sebelum sesi disahkan. Masih ada {$unrecorded} siswa yang belum dicatat.",
            ]);
        }

        $user = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($session, $user) {
                $session->update([
                    'status' => AttendanceSession::STATUS_FINALIZED,
                    'finalized_at' => now(),
                    'finalized_by' => $user->id,
                    'version' => $session->version + 1,
                ]);

                return $session;
            },
            auditFactory: fn (AttendanceSession $session) => [
                'actor_id' => $user->id,
                'action' => 'FINALIZE_ATTENDANCE_SESSION',
                'entity_type' => 'attendance_session',
                'entity_id' => $session->id,
                'scope' => AuditScope::ATTENDANCE,
                'changes' => [
                    'status' => 'finalized',
                    'total_records_frozen' => $recordedCount,
                    'finalized_at' => now()->toIso8601String(),
                ],
                'reason' => "Pengesahan resmi presensi pertemuan ke-{$session->meeting_number}",
            ]
        );

        return back()->with('success', "Sesi kehadiran pertemuan ke-{$session->meeting_number} berhasil disahkan.");
    }

    /**
     * Delete a draft attendance session.
     */
    public function destroy(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorize('delete', $session);

        if ($session->isFinalized()) {
            throw ValidationException::withMessages([
                'message' => 'Sesi kehadiran yang telah disahkan (finalized) tidak dapat dihapus.',
            ]);
        }

        $classSubjectId = $session->class_subject_id;
        $meetingNumber = $session->meeting_number;

        $this->auditService->executeAtomic(
            operation: function () use ($session) {
                $session->records()->delete();
                $session->delete();

                return true;
            },
            auditFactory: fn () => [
                'actor_id' => $request->user()->id,
                'action' => 'DELETE_ATTENDANCE_SESSION',
                'entity_type' => 'attendance_session',
                'entity_id' => $session->id,
                'scope' => AuditScope::ATTENDANCE,
                'changes' => [
                    'class_subject_id' => $classSubjectId,
                    'meeting_number' => $meetingNumber,
                ],
                'reason' => "Menghapus draf sesi pertemuan ke-{$meetingNumber}",
            ]
        );

        return redirect()->route('academic.class-subjects.attendance.index', $classSubjectId)
            ->with('success', "Draf pertemuan ke-{$meetingNumber} berhasil dihapus.");
    }

    /**
     * Grant data entry delegation for a class subject (Q6.3).
     */
    public function storeDelegation(Request $request, ClassSubject $classSubject): RedirectResponse
    {
        $this->authorize('manageDelegations', AttendanceSession::class);

        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
            ],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:valid_from'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $targetUser = User::findOrFail($validated['user_id']);
        if (! $targetUser->hasRole(Role::DATA_ENTRY_ADMIN)) {
            throw ValidationException::withMessages([
                'user_id' => 'Pengguna yang dipilih harus memiliki peran Data Entry Admin.',
            ]);
        }

        $activeAssignment = $classSubject->activeTeachingAssignment;
        if (! $activeAssignment) {
            throw ValidationException::withMessages([
                'user_id' => 'Mata pelajaran kelas ini belum memiliki guru pengampu aktif untuk didelegasikan.',
            ]);
        }

        $user = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($targetUser, $activeAssignment, $validated, $user) {
                return DataEntryDelegation::create([
                    'user_id' => $targetUser->id,
                    'teaching_assignment_id' => $activeAssignment->id,
                    'capability' => 'attendance_entry',
                    'valid_from' => $validated['valid_from'],
                    'valid_until' => $validated['valid_until'],
                    'granted_by' => $user->id,
                    'reason' => $validated['reason'] ?? null,
                ]);
            },
            auditFactory: fn (DataEntryDelegation $delegation) => [
                'actor_id' => $user->id,
                'action' => 'GRANT_DATA_ENTRY_DELEGATION',
                'entity_type' => 'data_entry_delegation',
                'entity_id' => $delegation->id,
                'scope' => AuditScope::ATTENDANCE,
                'changes' => [
                    'user_id' => $targetUser->id,
                    'teaching_assignment_id' => $activeAssignment->id,
                    'capability' => 'attendance_entry',
                    'valid_from' => $validated['valid_from'],
                    'valid_until' => $validated['valid_until'],
                ],
                'reason' => 'Memberikan delegasi input presensi kepada Data Entry Admin',
            ]
        );

        return back()->with('success', "Delegasi input presensi berhasil diberikan kepada {$targetUser->name}.");
    }

    /**
     * Revoke data entry delegation (Q6.3).
     */
    public function revokeDelegation(Request $request, DataEntryDelegation $delegation): RedirectResponse
    {
        $this->authorize('manageDelegations', AttendanceSession::class);

        if ($delegation->revoked_at !== null) {
            return back()->with('info', 'Delegasi sudah dicabut sebelumnya.');
        }

        $this->auditService->executeAtomic(
            operation: function () use ($delegation) {
                $delegation->update(['revoked_at' => now()]);

                return $delegation;
            },
            auditFactory: fn (DataEntryDelegation $delegation) => [
                'actor_id' => $request->user()->id,
                'action' => 'REVOKE_DATA_ENTRY_DELEGATION',
                'entity_type' => 'data_entry_delegation',
                'entity_id' => $delegation->id,
                'scope' => AuditScope::ATTENDANCE,
                'changes' => [
                    'delegation_id' => $delegation->id,
                    'revoked_at' => now()->toIso8601String(),
                ],
                'reason' => 'Mencabut delegasi input presensi',
            ]
        );

        return back()->with('success', 'Delegasi input presensi berhasil dicabut.');
    }
}
