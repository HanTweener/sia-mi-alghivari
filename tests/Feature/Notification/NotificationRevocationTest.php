<?php

namespace Tests\Feature\Notification;

use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use App\Enums\Role;
use App\Events\InboxChanged;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\GradingPolicy;
use App\Models\HomeroomAssignment;
use App\Models\Notification;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\StudentProgression;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationRevocationTest extends TestCase
{
    use RefreshDatabase;

    private ReportCard $report;

    private StudentProgression $progression;

    protected function setUp(): void
    {
        parent::setUp();
        $actor = User::factory()->create();
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30']);
        $term = Term::create([
            'academic_year_id' => $year->id, 'semester' => 1, 'status' => 'active',
            'starts_on' => '2026-07-01', 'ends_on' => '2026-12-31',
        ]);
        $classroom = Classroom::create(['term_id' => $term->id, 'grade_level' => 1, 'section' => 'A']);
        $student = Student::create(['nis' => 'NOTIFY-1', 'name' => 'Siswa Uji', 'gender' => 'L', 'status' => 'active']);
        $enrollment = Enrollment::create([
            'student_id' => $student->id, 'classroom_id' => $classroom->id,
            'term_id' => $term->id, 'starts_on' => '2026-07-01', 'status' => 'active',
        ]);
        $policy = GradingPolicy::create(['revision' => 1, 'kkm' => '75.00', 'status' => 'active', 'created_by' => $actor->id]);
        $this->report = ReportCard::create([
            'enrollment_id' => $enrollment->id, 'term_id' => $term->id, 'grading_policy_id' => $policy->id,
            'status' => ReportCardStatus::PUBLISHED, 'availability' => ReportCardAvailability::CURRENT, 'revision' => 1,
        ]);
        $this->progression = StudentProgression::create([
            'student_id' => $student->id, 'source_academic_year_id' => $year->id,
            'source_report_card_id' => $this->report->id, 'type' => 'promotion', 'status' => 'submitted', 'proposed_by' => $actor->id,
        ]);
    }

    private function scopedNotification(User $recipient, string $scope, int $id): Notification
    {
        return Notification::factory()->create([
            'recipient_id' => $recipient->id, 'scope_type' => $scope, 'scope_id' => (string) $id,
            'sanitized_summary' => 'Ringkasan akademik privat', 'read_at' => null,
        ]);
    }

    private function assertHiddenEverywhere(User $user, Notification $notification): void
    {
        $this->actingAs($user)->getJson('/notifications')->assertOk()
            ->assertJsonCount(0, 'notifications')->assertJsonPath('unread_count', 0)
            ->assertJsonMissing(['sanitized_summary' => $notification->sanitized_summary]);
        $this->getJson('/notifications/unread-count')->assertExactJson(['count' => 0]);
        $this->get('/inbox')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('notifications.data', 0)->where('notifications.total', 0)->where('unreadCount', 0));
        $this->patchJson("/notifications/{$notification->id}/read-state")->assertForbidden();
        $this->postJson('/notifications/mark-all-read')->assertOk();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_guardian_link_revocation_removes_summary_counts_and_read_access(): void
    {
        $guardian = User::factory()->create();
        $guardian->assignRole(Role::GUARDIAN);
        $student = $this->report->enrollment->student;
        $student->guardians()->attach($guardian, ['relationship' => 'ayah', 'is_primary' => true]);
        $notification = $this->scopedNotification($guardian, 'report', $this->report->id);

        $this->actingAs($guardian)->getJson('/notifications')->assertOk()->assertJsonCount(1, 'notifications')->assertJsonPath('unread_count', 1);
        $student->guardians()->detach($guardian);

        $this->assertHiddenEverywhere($guardian, $notification);
    }

    public function test_unpublished_or_superseded_reports_are_hidden_from_guardians(): void
    {
        $guardian = User::factory()->create();
        $guardian->assignRole(Role::GUARDIAN);
        $this->report->enrollment->student->guardians()->attach($guardian, ['relationship' => 'ayah', 'is_primary' => true]);
        $notification = $this->scopedNotification($guardian, 'report_card', $this->report->id);
        $this->assertTrue(Notification::visibleTo($guardian)->whereKey($notification)->exists());

        $this->report->update(['availability' => ReportCardAvailability::SUPERSEDED]);
        $this->assertHiddenEverywhere($guardian, $notification);
        $this->report->update(['availability' => ReportCardAvailability::CURRENT, 'status' => ReportCardStatus::REVOKED]);
        $this->assertHiddenEverywhere($guardian, $notification);
    }

    public function test_revoked_homeroom_hides_report_and_progression_before_pagination(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(Role::TEACHER);
        $assignment = HomeroomAssignment::create([
            'classroom_id' => $this->report->enrollment->classroom_id, 'teacher_id' => $teacher->id,
            'status' => 'active', 'assigned_by' => $teacher->id,
        ]);
        $reportNotification = $this->scopedNotification($teacher, 'report', $this->report->id);
        $progressionNotification = $this->scopedNotification($teacher, 'progression', $this->progression->id);
        $this->assertSame(2, Notification::visibleTo($teacher)->count());
        $assignment->update(['status' => 'inactive']);
        $this->assertHiddenEverywhere($teacher, $reportNotification);
        $this->assertHiddenEverywhere($teacher, $progressionNotification);

        Notification::factory()->count(16)->create([
            'recipient_id' => $teacher->id, 'scope_type' => 'report', 'scope_id' => (string) $this->report->id,
        ]);
        $own = Notification::factory()->create(['recipient_id' => $teacher->id, 'scope_type' => 'USER', 'scope_id' => $teacher->id]);
        $this->get('/inbox')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('notifications.data', 1)->where('notifications.total', 1)->where('notifications.data.0.id', $own->id));
        $this->getJson('/notifications')->assertOk()->assertJsonCount(1, 'notifications')->assertJsonPath('unread_count', 1);
        $this->postJson('/notifications/mark-all-read')->assertOk();
        $this->assertNotNull($own->fresh()->read_at);
        $this->assertNull($reportNotification->fresh()->read_at);
    }

    public function test_principal_role_revocation_uses_current_database_roles(): void
    {
        $principal = User::factory()->create();
        $principal->assignRole(Role::PRINCIPAL);
        $principal->load('userRoles');
        $notification = $this->scopedNotification($principal, 'progression', $this->progression->id);
        $this->assertSame(1, Notification::visibleTo($principal)->count());

        $principal->removeRole(Role::PRINCIPAL);
        $this->assertSame(0, Notification::visibleTo($principal)->count());
        $this->assertHiddenEverywhere($principal, $notification);
    }

    public function test_unknown_foreign_account_and_missing_resource_scopes_fail_closed(): void
    {
        $master = User::factory()->create();
        $master->assignRole(Role::MASTER_ADMIN);
        $foreign = User::factory()->create();
        $unknown = $this->scopedNotification($master, 'unknown_academic_scope', $this->report->id);
        $this->scopedNotification($master, 'USER', $foreign->id);
        $this->scopedNotification($master, 'report', $this->report->id + 1000);
        $this->assertHiddenEverywhere($master, $unknown);
    }

    public function test_guardian_grant_survives_homeroom_revocation_but_never_grants_progression(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::TEACHER);
        $user->assignRole(Role::GUARDIAN);
        $this->report->enrollment->student->guardians()->attach($user, ['relationship' => 'ayah', 'is_primary' => true]);
        $assignment = HomeroomAssignment::create([
            'classroom_id' => $this->report->enrollment->classroom_id, 'teacher_id' => $user->id,
            'status' => 'active', 'assigned_by' => $user->id,
        ]);
        $reportNotification = $this->scopedNotification($user, 'report', $this->report->id);
        $progressionNotification = $this->scopedNotification($user, 'progression', $this->progression->id);
        $assignment->update(['status' => 'inactive']);

        $this->actingAs($user)->getJson('/notifications')->assertOk()->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.id', $reportNotification->id)->assertJsonPath('unread_count', 1);
        $this->patchJson("/notifications/{$reportNotification->id}/read-state")->assertOk();
        $this->patchJson("/notifications/{$progressionNotification->id}/read-state")->assertForbidden();
    }

    public function test_inactive_recipient_has_no_visible_notifications_and_realtime_only_sends_hint(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['recipient_id' => $user->id]);
        $this->assertSame(1, Notification::visibleTo($user)->count());
        $user->update(['is_active' => false]);
        $this->assertSame(0, Notification::visibleTo($user)->count());
        $this->assertSame(['notification_id' => $notification->id], (new InboxChanged($notification))->broadcastWith());
    }
}
