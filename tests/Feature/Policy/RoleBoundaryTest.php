<?php

namespace Tests\Feature\Policy;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\DataEntryDelegation;
use App\Models\Enrollment;
use App\Models\HomeroomAssignment;
use App\Models\ScoringComponent;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\Services\Auth\PasswordLifecycleService;
use Fouladgar\OTP\Facades\OTP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Classroom $classroom;

    private ClassSubject $subject;

    private TeachingAssignment $assignment;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 2)->startOfDay());
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30']);
        $term = Term::create(['academic_year_id' => $year->id, 'semester' => 1, 'starts_on' => '2026-07-01', 'ends_on' => '2026-12-31', 'status' => 'active']);
        $this->classroom = Classroom::create(['term_id' => $term->id, 'grade_level' => 1, 'section' => 'A', 'roster_version' => 1]);
        $catalogSubject = Subject::create(['code' => 'TEST', 'name' => 'Mapel Uji', 'category' => 'umum', 'is_active' => true]);
        $this->subject = $this->classroom->classSubjects()->create(['subject_id' => $catalogSubject->id]);
        $this->teacher = $this->user(Role::TEACHER);
        $this->assignment = $this->subject->teachingAssignments()->create(['teacher_id' => $this->teacher->id, 'starts_on' => '2026-07-01', 'status' => 'assigned']);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public static function roles(): array
    {
        return array_map(fn (Role $role) => [$role], Role::cases());
    }

    #[DataProvider('roles')]
    public function test_account_administration_and_settings_are_master_only(Role $role): void
    {
        $user = $this->user($role);
        $this->actingAs($user);
        $expected = $role === Role::MASTER_ADMIN ? 200 : 403;
        $this->get('/admin/users')->assertStatus($expected);
        $this->get('/admin/settings')->assertStatus($expected);
        if ($role !== Role::MASTER_ADMIN) {
            $this->post('/admin/users', [])->assertForbidden();
            $this->put("/admin/users/{$this->teacher->id}", [])->assertForbidden();
            $this->delete("/admin/users/{$this->teacher->id}")->assertForbidden();
            $this->put('/admin/settings', [])->assertForbidden();
            $this->assertTrue($this->teacher->fresh()->is_active);
        }
    }

    public function test_teacher_student_list_and_roster_do_not_disclose_unrelated_students_or_guardians(): void
    {
        $own = Student::create(['nis' => 'OWN', 'name' => 'Siswa Binaan', 'gender' => 'L', 'status' => 'active']);
        $other = Student::create(['nis' => 'OTHER', 'name' => 'Siswa Luar', 'gender' => 'P', 'status' => 'active']);
        $guardian = $this->user(Role::GUARDIAN);
        $own->guardians()->attach($guardian, ['relationship' => 'wali', 'is_primary' => true]);
        $this->classroom->enrollments()->create(['student_id' => $own->id, 'term_id' => $this->classroom->term_id, 'starts_on' => '2026-07-01', 'status' => 'active']);
        $this->actingAs($this->teacher)->get('/academic/students')->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 1)->where('students.data.0.id', $own->id)
            ->missing('students.data.0.birth_date')->where('students.data.0.guardians', [])
            ->where('availableGuardians', []));
        $this->get('/academic/students/export')->assertForbidden();
        $this->get("/academic/classrooms/{$this->classroom->id}/roster")->assertInertia(fn (Assert $page) => $page
            ->where('availableStudents', [])->where('teachers', [])->where('otherClassrooms', [])
            ->missing('enrollments.0.student.birth_date'));
        $this->assertNotSame($own->id, $other->id);
    }

    public function test_teacher_cannot_open_an_unassigned_classroom(): void
    {
        $other = Classroom::create(['term_id' => $this->classroom->term_id, 'grade_level' => 1, 'section' => 'B']);
        $this->actingAs($this->teacher)->get("/academic/classrooms/{$other->id}/roster")->assertForbidden();
        $this->get("/academic/classrooms/{$other->id}/subjects")->assertForbidden();
    }

    public function test_old_teacher_and_old_delegation_lose_both_grade_and_attendance_access(): void
    {
        $delegate = $this->user(Role::DATA_ENTRY_ADMIN);
        foreach (['grade_entry', 'attendance_entry'] as $capability) {
            DataEntryDelegation::create(['user_id' => $delegate->id, 'teaching_assignment_id' => $this->assignment->id, 'capability' => $capability, 'valid_from' => '2026-07-01', 'valid_until' => '2026-12-31', 'granted_by' => $this->teacher->id, 'reason' => 'Uji delegasi']);
        }
        $this->assignment->update(['ends_on' => '2026-10-31']);
        $newTeacher = $this->user(Role::TEACHER);
        $this->subject->teachingAssignments()->create(['teacher_id' => $newTeacher->id, 'starts_on' => '2026-11-01', 'status' => 'assigned']);
        foreach ([$this->teacher, $delegate] as $user) {
            $this->actingAs($user)->get("/academic/class-subjects/{$this->subject->id}/assessments")->assertForbidden();
            $this->get("/academic/class-subjects/{$this->subject->id}/attendance-sessions")->assertForbidden();
        }
        $this->actingAs($newTeacher)->get("/academic/class-subjects/{$this->subject->id}/assessments")->assertOk();
    }

    public function test_future_teacher_has_no_access_before_assignment_starts(): void
    {
        $this->assignment->update(['starts_on' => '2026-11-03']);
        $this->actingAs($this->teacher)->get("/academic/class-subjects/{$this->subject->id}/assessments")->assertForbidden();
        $this->get("/academic/class-subjects/{$this->subject->id}/attendance-sessions")->assertForbidden();
    }

    public function test_data_entry_with_unrelated_teacher_role_cannot_clear_existing_attendance(): void
    {
        $delegate = $this->user(Role::DATA_ENTRY_ADMIN);
        $delegate->assignRole(Role::TEACHER);
        DataEntryDelegation::create(['user_id' => $delegate->id, 'teaching_assignment_id' => $this->assignment->id, 'capability' => 'attendance_entry', 'valid_from' => '2026-07-01', 'valid_until' => '2026-12-31', 'granted_by' => $this->teacher->id, 'reason' => 'Uji']);
        $student = Student::create(['nis' => 'ATT', 'name' => 'Siswa Uji', 'gender' => 'L']);
        $enrollment = Enrollment::create(['student_id' => $student->id, 'classroom_id' => $this->classroom->id, 'term_id' => $this->classroom->term_id, 'starts_on' => '2026-07-01', 'status' => 'active']);
        $session = AttendanceSession::create(['class_subject_id' => $this->subject->id, 'teaching_assignment_id' => $this->assignment->id, 'held_on' => '2026-11-02', 'meeting_number' => 1, 'status' => 'draft', 'version' => 1, 'created_by' => $this->teacher->id]);
        $record = AttendanceRecord::create(['attendance_session_id' => $session->id, 'enrollment_id' => $enrollment->id, 'status' => 'present', 'recorded_by' => $this->teacher->id]);
        $this->actingAs($delegate)->patch("/academic/attendance-sessions/{$session->id}/records", ['expected_version' => 1, 'expected_roster_version' => 1, 'entries' => [['enrollment_id' => $enrollment->id, 'status' => null]]])->assertSessionHasErrors('entries');
        $this->assertDatabaseHas('attendance_records', ['id' => $record->id, 'status' => 'present']);
    }

    public function test_data_entry_cannot_withdraw_or_transfer_enrollments(): void
    {
        $student = Student::create(['nis' => 'ENR', 'name' => 'Siswa Uji', 'gender' => 'L']);
        $enrollment = Enrollment::create(['student_id' => $student->id, 'classroom_id' => $this->classroom->id, 'term_id' => $this->classroom->term_id, 'starts_on' => '2026-07-01', 'status' => 'active']);
        $this->actingAs($this->user(Role::DATA_ENTRY_ADMIN))->delete("/academic/enrollments/{$enrollment->id}")->assertForbidden();
        $this->post("/academic/enrollments/{$enrollment->id}/transfer", [])->assertForbidden();
        $this->assertSame('active', $enrollment->fresh()->status);
    }

    public function test_homeroom_assignment_does_not_grant_progression_writes(): void
    {
        HomeroomAssignment::create(['classroom_id' => $this->classroom->id, 'teacher_id' => $this->teacher->id, 'status' => 'active']);
        $this->actingAs($this->teacher)->get('/academic/progressions')->assertOk();
        $this->post('/academic/progressions', ['classroom_id' => $this->classroom->id])->assertForbidden();
        $this->assertDatabaseCount('student_progressions', 0);
    }

    public function test_academic_audit_is_scoped_before_pagination_and_batch_expansion(): void
    {
        $academic = $this->user(Role::ACADEMIC_ADMIN);
        $visible = AuditLog::create(['actor_user_id' => $this->teacher->id, 'action' => 'UPDATE_STUDENT', 'entity_type' => Student::class, 'entity_id' => '1', 'scope' => 'student', 'event_visibility' => 'internal', 'allowed_changes' => [], 'batch_id' => 'shared-batch']);
        $hidden = AuditLog::create(['actor_user_id' => $this->teacher->id, 'action' => 'UPDATE_USER', 'entity_type' => User::class, 'entity_id' => '1', 'scope' => 'user', 'event_visibility' => 'internal', 'allowed_changes' => [], 'batch_id' => 'shared-batch']);
        $this->actingAs($academic)->getJson('/audit-logs')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $visible->id);
        $this->getJson("/audit-logs/{$hidden->id}")->assertForbidden();
        $this->getJson("/audit-logs/{$visible->id}")->assertOk()->assertJsonPath('batch_logs', []);
    }

    #[DataProvider('roles')]
    public function test_academic_configuration_and_delegation_mutations_require_administrators(Role $role): void
    {
        $this->actingAs($this->user($role));
        if (in_array($role, [Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN], true)) {
            $this->post('/academic/years', ['name' => '2027/2028', 'starts_on' => '2027-07-01', 'ends_on' => '2028-06-30'])->assertSessionHasNoErrors();
            $this->assertDatabaseHas('academic_years', ['name' => '2027/2028']);
        } else {
            foreach (['/academic/years', '/academic/grading-policies', '/academic/scoring-components', "/academic/classrooms/{$this->classroom->id}/homeroom", "/academic/class-subjects/{$this->subject->id}/teachers", "/academic/class-subjects/{$this->subject->id}/delegations"] as $url) {
                $this->post($url, [])->assertForbidden();
            }
            $this->assertDatabaseCount('data_entry_delegations', 0);
        }
    }

    public function test_principal_and_homeroom_read_grants_do_not_allow_grade_or_attendance_writes(): void
    {
        $component = ScoringComponent::create(['code' => 'UJI', 'name' => 'Uji', 'source_type' => 'assignment', 'is_active' => true]);
        $assessment = Assessment::create(['class_subject_id' => $this->subject->id, 'teaching_assignment_id' => $this->assignment->id, 'scoring_component_id' => $component->id, 'title' => 'Uji', 'assessed_on' => '2026-11-02', 'status' => 'draft', 'version' => 1, 'created_by' => $this->teacher->id]);
        $session = AttendanceSession::create(['class_subject_id' => $this->subject->id, 'teaching_assignment_id' => $this->assignment->id, 'held_on' => '2026-11-02', 'meeting_number' => 1, 'status' => 'draft', 'version' => 1, 'created_by' => $this->teacher->id]);
        $homeroom = $this->user(Role::TEACHER);
        HomeroomAssignment::create(['classroom_id' => $this->classroom->id, 'teacher_id' => $homeroom->id, 'status' => 'active']);
        foreach ([$this->user(Role::PRINCIPAL), $homeroom] as $reader) {
            $this->actingAs($reader)->get("/academic/assessments/{$assessment->id}")->assertInertia(fn (Assert $page) => $page
                ->missing('classSubject.teacher.phone')->missing('classSubject.teacher.address')->missing('classSubject.teacher.email'));
            $this->get("/academic/attendance-sessions/{$session->id}")->assertInertia(fn (Assert $page) => $page
                ->missing('session.class_subject.active_teaching_assignment.teacher.phone')
                ->missing('session.class_subject.active_teaching_assignment.teacher.address'));
            $this->get("/academic/class-subjects/{$this->subject->id}/assessments")->assertInertia(fn (Assert $page) => $page
                ->missing('classSubject.teacher.phone')->missing('classSubject.teacher.address'));
            $this->get("/academic/class-subjects/{$this->subject->id}/attendance-sessions")->assertInertia(fn (Assert $page) => $page
                ->missing('classSubject.active_teaching_assignment.teacher.phone')
                ->missing('classSubject.active_teaching_assignment.teacher.address'));
            $this->post("/academic/class-subjects/{$this->subject->id}/assessments", [])->assertForbidden();
            $this->post("/academic/class-subjects/{$this->subject->id}/attendance-sessions", [])->assertForbidden();
            $this->patch("/academic/assessments/{$assessment->id}/scores", [])->assertForbidden();
            $this->patch("/academic/attendance-sessions/{$session->id}/records", [])->assertForbidden();
            $this->post("/academic/assessments/{$assessment->id}/publish", [])->assertForbidden();
            $this->post("/academic/attendance-sessions/{$session->id}/finalize", [])->assertForbidden();
            $this->delete("/academic/assessments/{$assessment->id}")->assertForbidden();
            $this->delete("/academic/attendance-sessions/{$session->id}")->assertForbidden();
        }
    }

    public function test_role_cache_is_invalidated_when_roles_are_removed_or_added(): void
    {
        $user = $this->user(Role::TEACHER);
        $this->assertFalse($user->hasRole(Role::MASTER_ADMIN));
        $user->assignRole(Role::MASTER_ADMIN);
        $this->assertTrue($user->hasRole(Role::MASTER_ADMIN));
        $user->removeRole(Role::MASTER_ADMIN);
        $this->assertFalse($user->hasRole(Role::MASTER_ADMIN));
    }

    public function test_deactivation_then_reactivation_does_not_restore_old_sessions(): void
    {
        $master = $this->user(Role::MASTER_ADMIN);
        $target = $this->user(Role::TEACHER);
        $generation = $target->session_generation;
        $this->actingAs($master)->delete("/admin/users/{$target->id}")->assertSessionHasNoErrors();
        $this->assertGreaterThan($generation, $target->fresh()->session_generation);
        $target->update(['is_active' => true]);
        $this->actingAs($target)->withSession(['session_generation' => $generation])->get('/profile')->assertRedirect('/login');
    }

    public function test_student_biodata_cannot_bypass_graduation_or_data_entry_status_restrictions(): void
    {
        $student = Student::create(['nis' => 'STATUS', 'name' => 'Siswa Uji', 'gender' => 'L', 'status' => 'active']);
        $this->actingAs($this->user(Role::DATA_ENTRY_ADMIN));
        foreach (['inactive', 'transferred', 'graduated'] as $status) {
            $this->put("/academic/students/{$student->id}", ['name' => $student->name, 'gender' => 'L', 'status' => $status])->assertSessionHasErrors('status');
            $this->assertSame('active', $student->fresh()->status);
        }
        $this->actingAs($this->user(Role::MASTER_ADMIN))->put("/academic/students/{$student->id}", ['name' => $student->name, 'gender' => 'L', 'status' => 'graduated'])->assertSessionHasErrors('status');
    }

    public function test_deactivation_invalidates_reset_proof_and_otp_even_after_reactivation(): void
    {
        $master = $this->user(Role::MASTER_ADMIN);
        foreach (['delete', 'update'] as $action) {
            $target = $this->user(Role::TEACHER);
            $proof = app(PasswordLifecycleService::class)->generateResetProof($target);
            $broker = OTP::purpose('password_reset')->withNotify(false);
            $broker->send($target->email);
            $otp = $broker->getToken();
            $oldHash = $target->password;
            $this->actingAs($master);
            if ($action === 'delete') {
                $this->delete("/admin/users/{$target->id}")->assertSessionHasNoErrors();
            } else {
                $this->put("/admin/users/{$target->id}", ['name' => $target->name, 'is_active' => false, 'roles' => [Role::TEACHER->value]])->assertSessionHasNoErrors();
            }
            $target->refresh()->update(['is_active' => true]);
            $this->assertNull($target->password_reset_proof_hash);
            $this->app['auth']->forgetGuards();
            $this->withSession(['password_reset_proof' => $proof, 'password_reset_proof_email' => $target->email])
                ->post('/reset-password', ['email' => $target->email, 'token' => $proof, 'password' => 'Synthetic-new-password!', 'password_confirmation' => 'Synthetic-new-password!'])
                ->assertSessionHasErrors('token');
            $this->post('/reset-password/verify', ['email' => $target->email, 'otp' => $otp])->assertSessionHasErrors('otp');
            $this->assertSame($oldHash, $target->fresh()->password);
        }
    }
}
