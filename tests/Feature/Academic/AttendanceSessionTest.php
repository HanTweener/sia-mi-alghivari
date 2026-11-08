<?php

namespace Tests\Feature\Academic;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\DataEntryDelegation;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\Services\Grading\GradingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $otherTeacher;

    private User $dataEntry;

    private Classroom $classroom;

    private ClassSubject $classSubject;

    private TeachingAssignment $assignment;

    private Student $student1;

    private Student $student2;

    private Student $studentLate;

    private Enrollment $enrollment1;

    private Enrollment $enrollment2;

    private Enrollment $enrollmentLate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::ACADEMIC_ADMIN);

        $this->teacher = User::factory()->create(['name' => 'Guru Pengampu']);
        $this->teacher->assignRole(Role::TEACHER);

        $this->otherTeacher = User::factory()->create(['name' => 'Guru Lain']);
        $this->otherTeacher->assignRole(Role::TEACHER);

        $this->dataEntry = User::factory()->create(['name' => 'Petugas Entry']);
        $this->dataEntry->assignRole(Role::DATA_ENTRY_ADMIN);

        // Academic setup
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
        ]);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'semester' => 1,
            'status' => 'active',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-12-31',
        ]);

        $this->classroom = Classroom::create([
            'term_id' => $term->id,
            'grade_level' => 1,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        $subject = Subject::create([
            'code' => 'PAI',
            'name' => 'Pendidikan Agama Islam',
            'category' => 'PAI',
            'is_active' => true,
        ]);

        $this->classSubject = ClassSubject::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $subject->id,
        ]);

        $this->assignment = TeachingAssignment::create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
            'starts_on' => '2026-07-01',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // Students & Enrollments
        $this->student1 = Student::create(['nis' => '1001', 'name' => 'Ahmad', 'gender' => 'L', 'status' => 'active']);
        $this->student2 = Student::create(['nis' => '1002', 'name' => 'Budi', 'gender' => 'L', 'status' => 'active']);
        $this->studentLate = Student::create(['nis' => '1003', 'name' => 'Citra (Masuk Oktober)', 'gender' => 'P', 'status' => 'active']);

        // Ahmad & Budi active from July
        $this->enrollment1 = Enrollment::create([
            'student_id' => $this->student1->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $term->id,
            'starts_on' => '2026-07-01',
            'status' => 'active',
        ]);

        $this->enrollment2 = Enrollment::create([
            'student_id' => $this->student2->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $term->id,
            'starts_on' => '2026-07-01',
            'status' => 'active',
        ]);

        // Citra enrolled starting 2026-10-01
        $this->enrollmentLate = Enrollment::create([
            'student_id' => $this->studentLate->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $term->id,
            'starts_on' => '2026-10-01',
            'status' => 'active',
        ]);
    }

    public function test_teacher_can_view_sessions_and_create_draft(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get("/academic/class-subjects/{$this->classSubject->id}/attendance-sessions");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Academic/Attendance/Index'));

        // Teacher creates meeting 1 on 2026-08-01
        $createRes = $this->actingAs($this->teacher)
            ->post("/academic/class-subjects/{$this->classSubject->id}/attendance-sessions", [
                'meeting_number' => 1,
                'held_on' => '2026-08-01',
                'topic' => 'Pengenalan Huruf Hijaiyah',
            ]);

        $createRes->assertRedirect();
        $this->assertDatabaseHas('attendance_sessions', [
            'class_subject_id' => $this->classSubject->id,
            'meeting_number' => 1,
            'status' => 'draft',
            'version' => 1,
        ]);
    }

    public function test_other_teacher_cannot_view_or_manage_sessions(): void
    {
        // Teacher of another subject/class is forbidden
        $response = $this->actingAs($this->otherTeacher)
            ->get("/academic/class-subjects/{$this->classSubject->id}/attendance-sessions");

        $response->assertForbidden();

        $createRes = $this->actingAs($this->otherTeacher)
            ->post("/academic/class-subjects/{$this->classSubject->id}/attendance-sessions", [
                'meeting_number' => 1,
                'held_on' => '2026-08-01',
            ]);

        $createRes->assertForbidden();
    }

    public function test_draft_session_records_only_eligible_students_on_held_on(): void
    {
        // Meeting held on 2026-08-01 (before Citra enrolled on 2026-10-01)
        $session = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'status' => 'draft',
            'version' => 1,
        ]);

        $res = $this->actingAs($this->teacher)
            ->get("/academic/attendance-sessions/{$session->id}");

        $res->assertOk();
        $res->assertInertia(function ($page) {
            $students = $page->toArray()['props']['students'];
            $this->assertCount(2, $students); // Only Ahmad and Budi eligible on 2026-08-01
            $this->assertSame('1001', $students[0]['nis']);
            $this->assertSame('1002', $students[1]['nis']);
        });
    }

    public function test_draft_partial_records_saves_without_marking_empty_as_absent(): void
    {
        $session = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'status' => 'draft',
            'version' => 1,
        ]);

        // Ahmad marked present, Budi left blank (null)
        $patchRes = $this->actingAs($this->teacher)
            ->patch("/academic/attendance-sessions/{$session->id}/records", [
                'expected_version' => 1,
                'expected_roster_version' => 1,
                'entries' => [
                    ['enrollment_id' => $this->enrollment1->id, 'status' => 'present'],
                    ['enrollment_id' => $this->enrollment2->id, 'status' => null],
                ],
            ]);

        $patchRes->assertRedirect();

        // Ahmad has record present
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'enrollment_id' => $this->enrollment1->id,
            'status' => 'present',
        ]);

        // Budi does NOT have record (unrecorded, NOT absent!)
        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $session->id,
            'enrollment_id' => $this->enrollment2->id,
        ]);

        $this->assertSame(2, $session->fresh()->version);
    }

    public function test_cannot_finalize_session_when_some_students_are_unrecorded(): void
    {
        $session = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'status' => 'draft',
            'version' => 1,
        ]);

        // Only student 1 is recorded
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'enrollment_id' => $this->enrollment1->id,
            'status' => 'present',
            'recorded_by' => $this->teacher->id,
        ]);

        // Finalize attempt must fail because student 2 is unrecorded
        $finalizeRes = $this->actingAs($this->teacher)
            ->from("/academic/attendance-sessions/{$session->id}")
            ->post("/academic/attendance-sessions/{$session->id}/finalize", [
                'expected_version' => 1,
                'expected_roster_version' => 1,
            ]);

        $finalizeRes->assertRedirect();
        $finalizeRes->assertSessionHasErrors(['message']);
        $this->assertSame('draft', $session->fresh()->status);
    }

    public function test_can_finalize_session_when_all_eligible_students_are_recorded(): void
    {
        $session = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'status' => 'draft',
            'version' => 1,
        ]);

        // Ahmad present, Budi excused
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'enrollment_id' => $this->enrollment1->id,
            'status' => 'present',
            'recorded_by' => $this->teacher->id,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'enrollment_id' => $this->enrollment2->id,
            'status' => 'excused',
            'recorded_by' => $this->teacher->id,
        ]);

        $finalizeRes = $this->actingAs($this->teacher)
            ->post("/academic/attendance-sessions/{$session->id}/finalize", [
                'expected_version' => 1,
                'expected_roster_version' => 1,
            ]);

        $finalizeRes->assertRedirect();
        $fresh = $session->fresh();
        $this->assertSame('finalized', $fresh->status);
        $this->assertNotNull($fresh->finalized_at);
        $this->assertSame($this->teacher->id, $fresh->finalized_by);
    }

    public function test_finalized_session_cannot_be_updated_or_deleted(): void
    {
        $session = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'status' => 'finalized',
            'finalized_at' => now(),
            'finalized_by' => $this->teacher->id,
            'version' => 2,
        ]);

        // Try update records on finalized session
        $res = $this->actingAs($this->teacher)
            ->patch("/academic/attendance-sessions/{$session->id}/records", [
                'expected_version' => 2,
                'expected_roster_version' => 1,
                'entries' => [],
            ]);

        $res->assertForbidden();

        // Try delete finalized session
        $delRes = $this->actingAs($this->teacher)
            ->delete("/academic/attendance-sessions/{$session->id}");

        $delRes->assertForbidden();
    }

    public function test_roster_version_mismatch_prevents_stale_update(): void
    {
        $session = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'status' => 'draft',
            'version' => 1,
        ]);

        // Classroom roster version incremented to 2 (e.g. new student was added)
        $this->classroom->update(['roster_version' => 2]);

        $res = $this->actingAs($this->teacher)
            ->from("/academic/attendance-sessions/{$session->id}")
            ->patch("/academic/attendance-sessions/{$session->id}/records", [
                'expected_version' => 1,
                'expected_roster_version' => 1, // Client has stale roster version 1
                'entries' => [
                    ['enrollment_id' => $this->enrollment1->id, 'status' => 'present'],
                ],
            ]);

        $res->assertRedirect();
        $res->assertSessionHasErrors(['roster_changed']);
    }

    public function test_data_entry_with_active_delegation_can_draft_but_cannot_finalize_or_delete(): void
    {
        // 1. Without delegation, Data Entry is forbidden
        $this->actingAs($this->dataEntry)
            ->get("/academic/class-subjects/{$this->classSubject->id}/attendance-sessions")
            ->assertForbidden();

        // 2. Admin grants delegation to Data Entry
        DataEntryDelegation::create([
            'user_id' => $this->dataEntry->id,
            'teaching_assignment_id' => $this->assignment->id,
            'capability' => 'attendance_entry',
            'valid_from' => now()->subDays(5)->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'granted_by' => $this->admin->id,
        ]);

        // 3. Data Entry can now view sessions index
        $this->actingAs($this->dataEntry)
            ->get("/academic/class-subjects/{$this->classSubject->id}/attendance-sessions")
            ->assertOk();

        // 4. Data Entry creates draft session
        $this->actingAs($this->dataEntry)
            ->post("/academic/class-subjects/{$this->classSubject->id}/attendance-sessions", [
                'meeting_number' => 1,
                'held_on' => now()->toDateString(),
            ])
            ->assertRedirect();

        $session = AttendanceSession::where('class_subject_id', $this->classSubject->id)
            ->where('meeting_number', 1)
            ->firstOrFail();

        // 5. Data Entry can save draft records
        $this->actingAs($this->dataEntry)
            ->patch("/academic/attendance-sessions/{$session->id}/records", [
                'expected_version' => 1,
                'expected_roster_version' => 1,
                'entries' => [
                    ['enrollment_id' => $this->enrollment1->id, 'status' => 'present'],
                    ['enrollment_id' => $this->enrollment2->id, 'status' => 'present'],
                ],
            ])
            ->assertRedirect();

        // Records store delegation_id provenance
        $record = AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('enrollment_id', $this->enrollment1->id)
            ->firstOrFail();
        $this->assertNotNull($record->delegation_id);

        // 6. Data Entry CANNOT clear existing record
        $clearAttempt = $this->actingAs($this->dataEntry)
            ->from("/academic/attendance-sessions/{$session->id}")
            ->patch("/academic/attendance-sessions/{$session->id}/records", [
                'expected_version' => 2,
                'expected_roster_version' => 1,
                'entries' => [
                    ['enrollment_id' => $this->enrollment1->id, 'status' => null],
                ],
            ]);
        $clearAttempt->assertRedirect();
        $clearAttempt->assertSessionHasErrors(['entries']);

        // 7. Data Entry CANNOT finalize session (F-05 / D-27)
        $this->actingAs($this->dataEntry)
            ->post("/academic/attendance-sessions/{$session->id}/finalize", [
                'expected_version' => 2,
                'expected_roster_version' => 1,
            ])
            ->assertForbidden();

        // 8. Data Entry CANNOT delete session
        $this->actingAs($this->dataEntry)
            ->delete("/academic/attendance-sessions/{$session->id}")
            ->assertForbidden();
    }

    public function test_admin_can_manage_delegations_grant_and_revoke(): void
    {
        // Admin grants delegation
        $this->actingAs($this->admin)
            ->post("/academic/class-subjects/{$this->classSubject->id}/delegations", [
                'user_id' => $this->dataEntry->id,
                'valid_from' => '2026-08-01',
                'valid_until' => '2026-08-31',
                'reason' => 'Bantuan entri data awal semester',
            ])
            ->assertRedirect();

        $delegation = DataEntryDelegation::where('user_id', $this->dataEntry->id)->firstOrFail();
        $this->assertNull($delegation->revoked_at);

        // Admin revokes delegation
        $this->actingAs($this->admin)
            ->post("/academic/delegations/{$delegation->id}/revoke")
            ->assertRedirect();

        $this->assertNotNull($delegation->fresh()->revoked_at);
    }

    public function test_attendance_ratio_counts_only_finalized_sessions_for_eligible_student_and_integrates_with_calculator(): void
    {
        // Session 1: Finalized on 2026-08-01 (Citra not yet enrolled)
        $s1 = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'status' => 'finalized',
            'finalized_at' => now(),
            'finalized_by' => $this->teacher->id,
            'version' => 2,
        ]);
        AttendanceRecord::create(['attendance_session_id' => $s1->id, 'enrollment_id' => $this->enrollment1->id, 'status' => 'present', 'recorded_by' => $this->teacher->id]);
        AttendanceRecord::create(['attendance_session_id' => $s1->id, 'enrollment_id' => $this->enrollment2->id, 'status' => 'excused', 'recorded_by' => $this->teacher->id]);

        // Session 2: Finalized on 2026-08-15 (Citra not yet enrolled)
        $s2 = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 2,
            'held_on' => '2026-08-15',
            'status' => 'finalized',
            'finalized_at' => now(),
            'finalized_by' => $this->teacher->id,
            'version' => 2,
        ]);
        AttendanceRecord::create(['attendance_session_id' => $s2->id, 'enrollment_id' => $this->enrollment1->id, 'status' => 'present', 'recorded_by' => $this->teacher->id]);
        AttendanceRecord::create(['attendance_session_id' => $s2->id, 'enrollment_id' => $this->enrollment2->id, 'status' => 'sick', 'recorded_by' => $this->teacher->id]);

        // Session 3: Draft on 2026-09-01 (Draft MUST NOT be counted in denominator!)
        $s3 = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 3,
            'held_on' => '2026-09-01',
            'status' => 'draft',
            'version' => 1,
        ]);
        AttendanceRecord::create(['attendance_session_id' => $s3->id, 'enrollment_id' => $this->enrollment1->id, 'status' => 'present', 'recorded_by' => $this->teacher->id]);

        // Session 4: Finalized on 2026-10-15 (Citra enrolled on 2026-10-01, so Citra is eligible here)
        $s4 = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 4,
            'held_on' => '2026-10-15',
            'status' => 'finalized',
            'finalized_at' => now(),
            'finalized_by' => $this->teacher->id,
            'version' => 2,
        ]);
        AttendanceRecord::create(['attendance_session_id' => $s4->id, 'enrollment_id' => $this->enrollment1->id, 'status' => 'present', 'recorded_by' => $this->teacher->id]);
        AttendanceRecord::create(['attendance_session_id' => $s4->id, 'enrollment_id' => $this->enrollment2->id, 'status' => 'present', 'recorded_by' => $this->teacher->id]);
        AttendanceRecord::create(['attendance_session_id' => $s4->id, 'enrollment_id' => $this->enrollmentLate->id, 'status' => 'present', 'recorded_by' => $this->teacher->id]);

        // Query attendance statistics for Ahmad (enrollment1):
        // Ahmad eligible for s1, s2, s4 (all 3 finalized sessions, excluding draft s3) -> 3 present out of 3 sessions
        $ahmadSessions = AttendanceSession::where('class_subject_id', $this->classSubject->id)
            ->where('status', 'finalized')
            ->where('held_on', '>=', $this->enrollment1->starts_on)
            ->get();
        $this->assertCount(3, $ahmadSessions);

        $ahmadPresent = AttendanceRecord::whereIn('attendance_session_id', $ahmadSessions->pluck('id'))
            ->where('enrollment_id', $this->enrollment1->id)
            ->where('status', 'present')
            ->count();
        $this->assertSame(3, $ahmadPresent);

        // Query attendance statistics for Citra (enrollmentLate):
        // Citra enrolled 2026-10-01. Eligible only for s4! (1 session, not 3)
        $citraSessions = AttendanceSession::where('class_subject_id', $this->classSubject->id)
            ->where('status', 'finalized')
            ->where('held_on', '>=', $this->enrollmentLate->starts_on)
            ->get();
        $this->assertCount(1, $citraSessions);

        $citraPresent = AttendanceRecord::whereIn('attendance_session_id', $citraSessions->pluck('id'))
            ->where('enrollment_id', $this->enrollmentLate->id)
            ->where('status', 'present')
            ->count();
        $this->assertSame(1, $citraPresent);

        // Feed into DP-23's GradingCalculator
        $calculator = new GradingCalculator;
        $policy = [
            'kkm' => '70.00',
            'components' => [
                ['code' => 'hadir', 'name' => 'Kehadiran', 'source_type' => 'attendance', 'weight_percentage' => '100.00'],
            ],
        ];

        // Ahmad: 3/3 -> 100%
        $ahmadResult = $calculator->calculate($policy, [], ['present' => $ahmadPresent, 'total' => $ahmadSessions->count()]);
        $this->assertSame('100.00', $ahmadResult->finalScore);
        $this->assertTrue($ahmadResult->passedKkm);

        // Citra: 1/1 -> 100% (Not 1/3 = 33.33%!)
        $citraResult = $calculator->calculate($policy, [], ['present' => $citraPresent, 'total' => $citraSessions->count()]);
        $this->assertSame('100.00', $citraResult->finalScore);
        $this->assertTrue($citraResult->passedKkm);
    }
}
