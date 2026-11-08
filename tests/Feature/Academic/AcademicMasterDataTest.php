<?php

namespace Tests\Feature\Academic;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\HomeroomAssignment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_admin_can_create_academic_year_and_terms(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::MASTER_ADMIN);

        // 1. Create Academic Year
        $response = $this->actingAs($admin)->post('/academic/years', [
            'name' => '2026/2027',
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-06-20',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('academic_years', [
            'name' => '2026/2027',
        ]);

        $year = AcademicYear::where('name', '2026/2027')->first();

        // 2. Create Semester 1
        $termResponse = $this->actingAs($admin)->post("/academic/years/{$year->id}/terms", [
            'semester' => 1,
            'starts_on' => '2026-07-15',
            'ends_on' => '2026-12-20',
        ]);

        $termResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('terms', [
            'academic_year_id' => $year->id,
            'semester' => 1,
            'status' => 'draft',
        ]);

        // 3. Duplicate semester in same year is rejected
        $duplicateResponse = $this->actingAs($admin)->post("/academic/years/{$year->id}/terms", [
            'semester' => 1,
            'starts_on' => '2026-07-15',
            'ends_on' => '2026-12-20',
        ]);
        $duplicateResponse->assertSessionHasErrors(['semester']);
    }

    public function test_only_one_term_can_be_active_school_wide(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ACADEMIC_ADMIN);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-06-20',
        ]);

        $term1 = $year->terms()->create([
            'semester' => 1,
            'starts_on' => '2026-07-15',
            'ends_on' => '2026-12-20',
            'status' => 'active',
        ]);

        $term2 = $year->terms()->create([
            'semester' => 2,
            'starts_on' => '2027-01-05',
            'ends_on' => '2027-06-20',
            'status' => 'draft',
        ]);

        // Activate Term 2
        $response = $this->actingAs($admin)->patch("/academic/terms/{$term2->id}/activate");
        $response->assertSessionHasNoErrors();

        $term1->refresh();
        $term2->refresh();

        $this->assertEquals('closed', $term1->status);
        $this->assertEquals('active', $term2->status);

        // Audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'ACTIVATE_TERM',
            'entity_id' => (string) $term2->id,
        ]);
    }

    public function test_master_and_academic_admin_can_create_classroom(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::MASTER_ADMIN);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-06-20',
        ]);

        $term = $year->terms()->create([
            'semester' => 1,
            'starts_on' => '2026-07-15',
            'ends_on' => '2026-12-20',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post('/academic/classrooms', [
            'term_id' => $term->id,
            'grade_level' => 1,
            'section' => 'A',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('classrooms', [
            'term_id' => $term->id,
            'grade_level' => 1,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        // Duplicate section in same grade and term is rejected
        $duplicate = $this->actingAs($admin)->post('/academic/classrooms', [
            'term_id' => $term->id,
            'grade_level' => 1,
            'section' => 'A',
        ]);
        $duplicate->assertSessionHasErrors(['section']);
    }

    public function test_homeroom_teacher_only_sees_their_binaan_classroom_on_index(): void
    {
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-06-20',
        ]);
        $term = $year->terms()->create([
            'semester' => 1,
            'starts_on' => '2026-07-15',
            'ends_on' => '2026-12-20',
            'status' => 'active',
        ]);

        $myClass = Classroom::create([
            'term_id' => $term->id,
            'grade_level' => 1,
            'section' => 'A',
            'roster_version' => 1,
        ]);
        $otherClass = Classroom::create([
            'term_id' => $term->id,
            'grade_level' => 2,
            'section' => 'B',
            'roster_version' => 1,
        ]);

        $teacher = User::factory()->create();
        $teacher->assignRole(Role::TEACHER);

        HomeroomAssignment::create([
            'classroom_id' => $myClass->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($teacher)->get('/academic/classrooms');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Academic/Classrooms/Index')
            ->has('classrooms', 1)
            ->where('classrooms.0.id', $myClass->id)
            ->where('canCreate', false)
        );
    }

    public function test_pure_subject_teacher_only_sees_assigned_classrooms_on_index(): void
    {
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-06-20',
        ]);
        $term = $year->terms()->create([
            'semester' => 1,
            'starts_on' => '2026-07-15',
            'ends_on' => '2026-12-20',
            'status' => 'active',
        ]);

        $taughtClass = Classroom::create([
            'term_id' => $term->id,
            'grade_level' => 3,
            'section' => 'A',
            'roster_version' => 1,
        ]);
        $otherClass = Classroom::create([
            'term_id' => $term->id,
            'grade_level' => 4,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        $subject = Subject::create([
            'code' => 'PJOK',
            'name' => 'Olahraga',
            'category' => 'umum',
            'is_active' => true,
        ]);

        $classSubject = ClassSubject::create([
            'classroom_id' => $taughtClass->id,
            'subject_id' => $subject->id,
        ]);

        $subjectTeacher = User::factory()->create();
        $subjectTeacher->assignRole(Role::TEACHER);

        TeachingAssignment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $subjectTeacher->id,
            'status' => 'assigned',
            'starts_on' => '2026-07-15',
        ]);

        $response = $this->actingAs($subjectTeacher)->get('/academic/classrooms');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Academic/Classrooms/Index')
            ->has('classrooms', 1)
            ->where('classrooms.0.id', $taughtClass->id)
            ->where('canCreate', false)
        );
    }

    public function test_master_and_academic_admin_can_manage_subjects(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ACADEMIC_ADMIN);

        // 1. Create Subject
        $response = $this->actingAs($admin)->post('/academic/subjects', [
            'code' => 'MTK',
            'name' => 'Matematika',
            'category' => 'umum',
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('subjects', [
            'code' => 'MTK',
            'name' => 'Matematika',
        ]);

        $subject = Subject::where('code', 'MTK')->first();

        // 2. Duplicate code is rejected
        $dup = $this->actingAs($admin)->post('/academic/subjects', [
            'code' => 'MTK',
            'name' => 'Matematika Lain',
            'category' => 'umum',
        ]);
        $dup->assertSessionHasErrors(['code']);

        // 3. Update Subject
        $updateResponse = $this->actingAs($admin)->put("/academic/subjects/{$subject->id}", [
            'name' => 'Matematika Terapan',
            'category' => 'umum',
            'is_active' => false,
        ]);
        $updateResponse->assertSessionHasNoErrors();

        $subject->refresh();
        $this->assertEquals('Matematika Terapan', $subject->name);
        $this->assertFalse($subject->is_active);
    }

    public function test_data_entry_admin_can_create_student_and_link_to_existing_guardian_q6_1(): void
    {
        // Q6.1: Master Admin prepares guardian user account first
        $guardianUser = User::factory()->create();
        $guardianUser->assignRole(Role::GUARDIAN);

        // Data Entry Admin inputs student and links guardian
        $dataEntry = User::factory()->create();
        $dataEntry->assignRole(Role::DATA_ENTRY_ADMIN);

        $response = $this->actingAs($dataEntry)->post('/academic/students', [
            'nis' => '2026001',
            'name' => 'Ahmad Fatih',
            'gender' => 'L',
            'birth_date' => '2018-05-12',
            'birth_place' => 'Bandung',
            'guardian_user_id' => $guardianUser->id,
            'guardian_relationship' => 'ayah',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('students', [
            'nis' => '2026001',
            'name' => 'Ahmad Fatih',
            'status' => 'active',
        ]);

        $student = Student::where('nis', '2026001')->first();
        $this->assertNotNull($student);
        $this->assertDatabaseHas('student_guardians', [
            'student_id' => $student->id,
            'guardian_user_id' => $guardianUser->id,
            'relationship' => 'ayah',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $dataEntry->id,
            'action' => 'CREATE_STUDENT',
            'entity_id' => (string) $student->id,
        ]);
    }

    public function test_data_entry_cannot_link_non_guardian_user(): void
    {
        // User with TEACHER role only (not GUARDIAN)
        $teacher = User::factory()->create();
        $teacher->assignRole(Role::TEACHER);

        $dataEntry = User::factory()->create();
        $dataEntry->assignRole(Role::DATA_ENTRY_ADMIN);

        $response = $this->actingAs($dataEntry)->post('/academic/students', [
            'nis' => '2026002',
            'name' => 'Siti Aisyah',
            'gender' => 'P',
            'guardian_user_id' => $teacher->id,
            'guardian_relationship' => 'ibu',
        ]);

        $response->assertSessionHasErrors(['guardian_user_id']);
        $this->assertDatabaseMissing('students', ['nis' => '2026002']);
    }

    public function test_data_entry_admin_cannot_manage_academic_years_or_classrooms(): void
    {
        $dataEntry = User::factory()->create();
        $dataEntry->assignRole(Role::DATA_ENTRY_ADMIN);

        // Attempt create academic year -> 403
        $this->actingAs($dataEntry)->post('/academic/years', [
            'name' => '2027/2028',
            'starts_on' => '2027-07-15',
            'ends_on' => '2028-06-20',
        ])->assertStatus(403);

        // Attempt create classroom -> 403
        $this->actingAs($dataEntry)->post('/academic/classrooms', [
            'term_id' => 1,
            'grade_level' => 1,
            'section' => 'A',
        ])->assertStatus(403);

        // Attempt create subject -> 403
        $this->actingAs($dataEntry)->post('/academic/subjects', [
            'code' => 'IPA',
            'name' => 'Ilmu Pengetahuan Alam',
            'category' => 'umum',
        ])->assertStatus(403);
    }
}
