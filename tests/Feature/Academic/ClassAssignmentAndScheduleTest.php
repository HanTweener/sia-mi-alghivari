<?php

namespace Tests\Feature\Academic;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\HomeroomAssignment;
use App\Models\ScheduleSlot;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassAssignmentAndScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Term $term;

    private Classroom $classroomA;

    private Classroom $classroomB;

    private User $teacherA;

    private User $teacherB;

    private Subject $mathSubject;

    private Subject $arabicSubject;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::ACADEMIC_ADMIN);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-06-20',
        ]);

        $this->term = Term::create([
            'academic_year_id' => $year->id,
            'semester' => 1,
            'starts_on' => '2026-07-15',
            'ends_on' => '2026-12-20',
            'status' => 'active',
        ]);

        $this->classroomA = Classroom::create([
            'term_id' => $this->term->id,
            'grade_level' => 1,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        $this->classroomB = Classroom::create([
            'term_id' => $this->term->id,
            'grade_level' => 1,
            'section' => 'B',
            'roster_version' => 1,
        ]);

        $this->teacherA = User::factory()->create(['name' => 'Ustadz Ahmad']);
        $this->teacherA->assignRole(Role::TEACHER);

        $this->teacherB = User::factory()->create(['name' => 'Ustadzah Fatimah']);
        $this->teacherB->assignRole(Role::TEACHER);

        $this->mathSubject = Subject::create([
            'code' => 'MTK',
            'name' => 'Matematika',
            'category' => 'umum',
            'order_index' => 1,
            'status' => 'active',
        ]);

        $this->arabicSubject = Subject::create([
            'code' => 'BA',
            'name' => 'Bahasa Arab',
            'category' => 'agama',
            'order_index' => 2,
            'status' => 'active',
        ]);

        $this->student = Student::create([
            'nis' => '2026001',
            'name' => 'Muhammad Ali',
            'gender' => 'L',
            'birth_place' => 'Bandung',
            'birth_date' => '2019-05-10',
            'enrollment_date' => '2026-07-15',
            'status' => 'active',
        ]);
    }

    public function test_can_assign_homeroom_teacher_and_inactivates_previous_one(): void
    {
        // 1. Assign Teacher A as homeroom
        $response1 = $this->actingAs($this->admin)->post("/academic/classrooms/{$this->classroomA->id}/homeroom", [
            'teacher_id' => $this->teacherA->id,
        ]);

        $response1->assertSessionHasNoErrors();
        $this->assertDatabaseHas('homeroom_assignments', [
            'classroom_id' => $this->classroomA->id,
            'teacher_id' => $this->teacherA->id,
            'status' => 'active',
        ]);

        // 2. Assign Teacher B as homeroom to same classroom
        $response2 = $this->actingAs($this->admin)->post("/academic/classrooms/{$this->classroomA->id}/homeroom", [
            'teacher_id' => $this->teacherB->id,
        ]);

        $response2->assertSessionHasNoErrors();

        // Previous assignment for Teacher A is now inactive
        $this->assertDatabaseHas('homeroom_assignments', [
            'classroom_id' => $this->classroomA->id,
            'teacher_id' => $this->teacherA->id,
            'status' => 'inactive',
        ]);

        // New assignment for Teacher B is active
        $this->assertDatabaseHas('homeroom_assignments', [
            'classroom_id' => $this->classroomA->id,
            'teacher_id' => $this->teacherB->id,
            'status' => 'active',
        ]);

        // Invariant: Max 1 active per room
        $activeCount = HomeroomAssignment::where('classroom_id', $this->classroomA->id)
            ->where('status', 'active')
            ->count();
        $this->assertSame(1, $activeCount);
    }

    public function test_student_enrollment_in_classroom_increments_roster_version(): void
    {
        $this->assertSame(1, $this->classroomA->roster_version);

        // Enroll student
        $response = $this->actingAs($this->admin)->post("/academic/classrooms/{$this->classroomA->id}/enrollments", [
            'student_id' => $this->student->id,
            'starts_on' => '2026-07-15',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroomA->id,
            'term_id' => $this->term->id,
            'status' => 'active',
        ]);

        // Roster version incremented to 2
        $this->classroomA->refresh();
        $this->assertSame(2, $this->classroomA->roster_version);

        // Withdraw student
        $enrollment = Enrollment::where('student_id', $this->student->id)->first();
        $withdrawResponse = $this->actingAs($this->admin)->delete("/academic/enrollments/{$enrollment->id}");
        $withdrawResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => 'withdrawn',
        ]);

        // Roster version incremented to 3
        $this->classroomA->refresh();
        $this->assertSame(3, $this->classroomA->roster_version);
    }

    public function test_student_cannot_be_enrolled_in_multiple_classrooms_in_same_term(): void
    {
        // Enroll in Classroom A
        $this->actingAs($this->admin)->post("/academic/classrooms/{$this->classroomA->id}/enrollments", [
            'student_id' => $this->student->id,
        ])->assertSessionHasNoErrors();

        // Attempt to enroll in Classroom B in same term
        $duplicateResponse = $this->actingAs($this->admin)->post("/academic/classrooms/{$this->classroomB->id}/enrollments", [
            'student_id' => $this->student->id,
        ]);

        $duplicateResponse->assertSessionHasErrors(['student_id']);
    }

    public function test_can_link_subject_to_classroom_and_prevent_duplicates(): void
    {
        // Link Math to Classroom A
        $response = $this->actingAs($this->admin)->post("/academic/classrooms/{$this->classroomA->id}/subjects", [
            'subject_id' => $this->mathSubject->id,
            'teacher_id' => $this->teacherA->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('class_subjects', [
            'classroom_id' => $this->classroomA->id,
            'subject_id' => $this->mathSubject->id,
        ]);

        $classSubject = ClassSubject::where('classroom_id', $this->classroomA->id)
            ->where('subject_id', $this->mathSubject->id)
            ->first();

        // Initial teacher assignment created
        $this->assertDatabaseHas('teaching_assignments', [
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacherA->id,
            'status' => 'assigned',
        ]);

        // Duplicate link fails
        $duplicateResponse = $this->actingAs($this->admin)->post("/academic/classrooms/{$this->classroomA->id}/subjects", [
            'subject_id' => $this->mathSubject->id,
        ]);
        $duplicateResponse->assertSessionHasErrors(['subject_id']);
    }

    public function test_teaching_assignment_handover_closes_previous_active_assignment(): void
    {
        // 1. Setup ClassSubject with Teacher A
        $classSubject = ClassSubject::create([
            'classroom_id' => $this->classroomA->id,
            'subject_id' => $this->mathSubject->id,
        ]);

        $assignmentA = TeachingAssignment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacherA->id,
            'starts_on' => '2026-07-15',
            'status' => 'assigned',
        ]);

        $this->assertNull($assignmentA->ends_on);

        // 2. Handover to Teacher B starting 2026-09-01
        $handoverResponse = $this->actingAs($this->admin)->post("/academic/class-subjects/{$classSubject->id}/teachers", [
            'teacher_id' => $this->teacherB->id,
            'starts_on' => '2026-09-01',
        ]);

        $handoverResponse->assertSessionHasNoErrors();

        // Historical assignment A is closed (ends_on is set)
        $assignmentA->refresh();
        $this->assertSame('2026-09-01', $assignmentA->ends_on->format('Y-m-d'));

        // New assignment B is active with null ends_on
        $this->assertDatabaseHas('teaching_assignments', [
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacherB->id,
            'ends_on' => null,
            'status' => 'assigned',
        ]);
        $this->assertSame('2026-09-01', $classSubject->activeTeachingAssignment->starts_on->format('Y-m-d'));

        // Historical count is 2, active is 1
        $this->assertSame(2, $classSubject->teachingAssignments()->count());
        $this->assertSame($this->teacherB->id, $classSubject->activeTeachingAssignment->teacher_id);
    }

    public function test_schedule_slot_conflict_validation_for_classroom(): void
    {
        $classSubject1 = ClassSubject::create([
            'classroom_id' => $this->classroomA->id,
            'subject_id' => $this->mathSubject->id,
        ]);

        $classSubject2 = ClassSubject::create([
            'classroom_id' => $this->classroomA->id,
            'subject_id' => $this->arabicSubject->id,
        ]);

        // Schedule Slot 1: Monday 07:30 - 08:50
        $this->actingAs($this->admin)->post("/academic/class-subjects/{$classSubject1->id}/schedules", [
            'weekday' => 1,
            'starts_at' => '07:30',
            'ends_at' => '08:50',
        ])->assertSessionHasNoErrors();

        // Attempt overlapping Slot in SAME classroom: Monday 08:00 - 09:20
        $overlapResponse = $this->actingAs($this->admin)->post("/academic/class-subjects/{$classSubject2->id}/schedules", [
            'weekday' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:20',
        ]);

        $overlapResponse->assertSessionHasErrors(['schedule_conflict']);
    }

    public function test_schedule_slot_conflict_validation_for_teacher(): void
    {
        // Teacher A teaches Math in Classroom A
        $classSubjectA = ClassSubject::create([
            'classroom_id' => $this->classroomA->id,
            'subject_id' => $this->mathSubject->id,
        ]);
        TeachingAssignment::create([
            'class_subject_id' => $classSubjectA->id,
            'teacher_id' => $this->teacherA->id,
            'starts_on' => '2026-07-15',
            'status' => 'assigned',
        ]);

        // Slot 1: Monday 07:30 - 08:50 in Classroom A
        $this->actingAs($this->admin)->post("/academic/class-subjects/{$classSubjectA->id}/schedules", [
            'weekday' => 1,
            'starts_at' => '07:30',
            'ends_at' => '08:50',
        ])->assertSessionHasNoErrors();

        // Teacher A also teaches Math in Classroom B
        $classSubjectB = ClassSubject::create([
            'classroom_id' => $this->classroomB->id,
            'subject_id' => $this->mathSubject->id,
        ]);
        TeachingAssignment::create([
            'class_subject_id' => $classSubjectB->id,
            'teacher_id' => $this->teacherA->id,
            'starts_on' => '2026-07-15',
            'status' => 'assigned',
        ]);

        // Attempt overlapping schedule for Teacher A in Classroom B: Monday 08:00 - 09:20
        $teacherConflictResponse = $this->actingAs($this->admin)->post("/academic/class-subjects/{$classSubjectB->id}/schedules", [
            'weekday' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:20',
        ]);

        $teacherConflictResponse->assertSessionHasErrors(['schedule_conflict']);
    }

    public function test_non_overlapping_schedule_slots_succeed(): void
    {
        $classSubject = ClassSubject::create([
            'classroom_id' => $this->classroomA->id,
            'subject_id' => $this->mathSubject->id,
        ]);

        // Slot 1: Monday 07:30 - 08:50
        $this->actingAs($this->admin)->post("/academic/class-subjects/{$classSubject->id}/schedules", [
            'weekday' => 1,
            'starts_at' => '07:30',
            'ends_at' => '08:50',
        ])->assertSessionHasNoErrors();

        // Slot 2: Monday 09:00 - 10:20 (after Slot 1, no overlap)
        $this->actingAs($this->admin)->post("/academic/class-subjects/{$classSubject->id}/schedules", [
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '10:20',
        ])->assertSessionHasNoErrors();

        // Slot 3: Tuesday 07:30 - 08:50 (different day)
        $this->actingAs($this->admin)->post("/academic/class-subjects/{$classSubject->id}/schedules", [
            'weekday' => 2,
            'starts_at' => '07:30',
            'ends_at' => '08:50',
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, ScheduleSlot::where('class_subject_id', $classSubject->id)->count());
    }

    public function test_can_render_classroom_roster_page_without_error(): void
    {
        $response = $this->actingAs($this->admin)->get("/academic/classrooms/{$this->classroomA->id}/roster");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Academic/Classrooms/Roster')
            ->has('classroom')
            ->has('teachers')
            ->has('availableStudents')
            ->has('enrollments')
        );
    }

    public function test_can_render_classroom_subjects_page_without_error(): void
    {
        $response = $this->actingAs($this->admin)->get("/academic/classrooms/{$this->classroomA->id}/subjects");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Academic/Classrooms/Subjects')
            ->has('classroom')
            ->has('teachers')
            ->has('availableSubjects')
            ->has('classSubjects')
        );
    }
}
