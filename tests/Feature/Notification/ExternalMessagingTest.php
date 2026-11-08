<?php

namespace Tests\Feature\Notification;

use App\Contracts\ExternalMessagingGateway;
use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use App\Enums\Role;
use App\Jobs\SendExternalNotificationJob;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\GradingPolicy;
use App\Models\HomeroomAssignment;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\Reports\ReportSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ExternalMessagingTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;

    protected User $homeroomTeacher;

    protected User $guardianWithPhone;

    protected User $guardianWithoutPhone;

    protected Student $student;

    protected ReportCard $finalizedReport;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->masterAdmin = User::factory()->create(['name' => 'Master Admin']);
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->homeroomTeacher = User::factory()->create(['name' => 'Wali Kelas']);
        $this->homeroomTeacher->assignRole(Role::TEACHER);

        $this->guardianWithPhone = User::factory()->create([
            'name' => 'Bapak Rudi',
            'phone' => '081234567890',
        ]);
        $this->guardianWithPhone->assignRole(Role::GUARDIAN);

        $this->guardianWithoutPhone = User::factory()->create([
            'name' => 'Ibu Siti',
            'phone' => null,
        ]);
        $this->guardianWithoutPhone->assignRole(Role::GUARDIAN);

        $year = AcademicYear::create([
            'name' => '2025/2026',
            'starts_on' => '2025-07-15',
            'ends_on' => '2026-06-20',
        ]);

        $term = Term::create([
            'academic_year_id' => $year->id,
            'semester' => 1,
            'starts_on' => '2025-07-15',
            'ends_on' => '2025-12-20',
            'status' => 'active',
        ]);

        $classroom = Classroom::create([
            'term_id' => $term->id,
            'grade_level' => 1,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        HomeroomAssignment::create([
            'classroom_id' => $classroom->id,
            'teacher_id' => $this->homeroomTeacher->id,
            'starts_on' => '2025-07-15',
            'status' => 'active',
        ]);

        $this->student = Student::create([
            'nis' => '1001',
            'name' => 'Ahmad Santoso',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->student->guardians()->attach([
            $this->guardianWithPhone->id => ['relationship' => 'ayah', 'is_primary' => true],
            $this->guardianWithoutPhone->id => ['relationship' => 'ibu', 'is_primary' => false],
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'classroom_id' => $classroom->id,
            'term_id' => $term->id,
            'starts_on' => '2025-07-15',
            'status' => 'active',
        ]);

        $policy = GradingPolicy::create([
            'revision' => 1,
            'kkm' => 70.00,
            'status' => 'active',
            'created_by' => $this->masterAdmin->id,
            'activated_by' => $this->masterAdmin->id,
            'activated_at' => now(),
        ]);

        $snapshotService = app(ReportSnapshotService::class);
        $snapshot = $snapshotService->calculateSnapshot($enrollment, $policy);

        $filePath = "reports/{$term->id}/sample.pdf";
        Storage::disk('local')->put($filePath, '%PDF-fake');

        $this->finalizedReport = ReportCard::create([
            'enrollment_id' => $enrollment->id,
            'term_id' => $term->id,
            'grading_policy_id' => $policy->id,
            'status' => ReportCardStatus::FINALIZED,
            'availability' => ReportCardAvailability::CURRENT,
            'source_version' => $snapshot['source_version'],
            'calculation_version' => '1.0',
            'version' => 1,
            'generated_at' => now(),
            'finalized_at' => now(),
            'finalized_by' => $this->homeroomTeacher->id,
            'private_file_path' => $filePath,
        ]);
    }

    public function test_publishing_report_card_dispatches_external_message_job_for_phone_guardians(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->homeroomTeacher)
            ->post(route('academic.report-cards.publish', $this->finalizedReport), [
                'expected_version' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 1. In-app notifications created for both guardians
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $this->guardianWithPhone->id,
            'event_category' => 'report_published',
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $this->guardianWithoutPhone->id,
            'event_category' => 'report_published',
        ]);

        // 2. Queued external message job dispatched ONLY for guardian with phone
        Queue::assertPushed(SendExternalNotificationJob::class, function ($job) {
            return $job->recipientPhone === '081234567890'
                && str_contains($job->message, 'Ahmad Santoso')
                && $job->context['channel'] === 'whatsapp';
        });

        Queue::assertPushed(SendExternalNotificationJob::class, 1);
    }

    public function test_external_gateway_failure_does_not_rollback_primary_database_state(): void
    {
        // Bind a failing gateway mock
        $failingGateway = Mockery::mock(ExternalMessagingGateway::class);
        $failingGateway->shouldReceive('send')
            ->andThrow(new \RuntimeException('Connection timed out to third-party WhatsApp gateway.'));

        $this->app->instance(ExternalMessagingGateway::class, $failingGateway);

        // Execute report card publication
        $response = $this->actingAs($this->homeroomTeacher)
            ->post(route('academic.report-cards.publish', $this->finalizedReport), [
                'expected_version' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Database state must be PUBLISHED (not rolled back by gateway failure)
        $this->finalizedReport->refresh();
        $this->assertSame(ReportCardStatus::PUBLISHED, $this->finalizedReport->status);
        $this->assertNotNull($this->finalizedReport->published_at);

        // In-app notifications are still saved
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $this->guardianWithPhone->id,
            'event_category' => 'report_published',
        ]);

        // Audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PUBLISH_REPORT_CARD',
            'entity_id' => $this->finalizedReport->id,
        ]);
    }

    public function test_job_invokes_external_gateway_correctly(): void
    {
        $mockGateway = Mockery::mock(ExternalMessagingGateway::class);
        $mockGateway->shouldReceive('send')
            ->once()
            ->with(
                '081234567890',
                'Pesan notifikasi rapor',
                ['channel' => 'whatsapp', 'report_id' => 10]
            )
            ->andReturn(true);

        $job = new SendExternalNotificationJob(
            recipientPhone: '081234567890',
            message: 'Pesan notifikasi rapor',
            context: ['channel' => 'whatsapp', 'report_id' => 10]
        );

        $job->handle($mockGateway);
    }
}
