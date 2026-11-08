<?php

namespace Tests\Feature\Notification;

use App\Enums\Role;
use App\Events\InboxChanged;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_inbox(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::TEACHER);

        $response = $this->actingAs($user)->get('/inbox');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Notifications/Index'));
    }

    public function test_inbox_is_strictly_scoped_to_recipient(): void
    {
        $userA = User::factory()->create();
        $userA->assignRole(Role::TEACHER);

        $userB = User::factory()->create();
        $userB->assignRole(Role::GUARDIAN);

        Notification::factory()->create([
            'recipient_id' => $userA->id,
            'sanitized_summary' => 'Notifikasi khusus User A',
        ]);

        Notification::factory()->create([
            'recipient_id' => $userB->id,
            'sanitized_summary' => 'Notifikasi khusus User B',
        ]);

        // User A requests API notifications
        $responseA = $this->actingAs($userA)->getJson('/notifications');
        $responseA->assertStatus(200);
        $responseA->assertJsonCount(1, 'notifications');
        $responseA->assertJsonFragment(['sanitized_summary' => 'Notifikasi khusus User A']);
        $responseA->assertJsonMissing(['sanitized_summary' => 'Notifikasi khusus User B']);

        // User B requests API notifications
        $responseB = $this->actingAs($userB)->getJson('/notifications');
        $responseB->assertStatus(200);
        $responseB->assertJsonCount(1, 'notifications');
        $responseB->assertJsonFragment(['sanitized_summary' => 'Notifikasi khusus User B']);
        $responseB->assertJsonMissing(['sanitized_summary' => 'Notifikasi khusus User A']);
    }

    public function test_unread_count_endpoint_returns_accurate_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::TEACHER);

        // 2 unread, 1 read
        Notification::factory()->count(2)->create([
            'recipient_id' => $user->id,
            'read_at' => null,
        ]);
        Notification::factory()->create([
            'recipient_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/notifications/unread-count');

        $response->assertStatus(200);
        $response->assertExactJson(['count' => 2]);
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::TEACHER);

        $notification = Notification::factory()->create([
            'recipient_id' => $user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->patchJson("/notifications/{$notification->id}/read-state");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userB->assignRole(Role::TEACHER);

        $notificationOfA = Notification::factory()->create([
            'recipient_id' => $userA->id,
            'read_at' => null,
        ]);

        // User B attempts to mark User A's notification as read
        $response = $this->actingAs($userB)->patchJson("/notifications/{$notificationOfA->id}/read-state");

        $response->assertStatus(403);

        $notificationOfA->refresh();
        $this->assertNull($notificationOfA->read_at);
    }

    public function test_user_can_mark_all_own_notifications_as_read(): void
    {
        $userA = User::factory()->create();
        $userA->assignRole(Role::TEACHER);

        $userB = User::factory()->create();

        // User A unread
        Notification::factory()->count(3)->create([
            'recipient_id' => $userA->id,
            'read_at' => null,
        ]);

        // User B unread
        $notificationB = Notification::factory()->create([
            'recipient_id' => $userB->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($userA)->postJson('/notifications/mark-all-read');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(0, $userA->notifications()->whereNull('read_at')->count());

        // User B's notification remains unread
        $notificationB->refresh();
        $this->assertNull($notificationB->read_at);
    }

    public function test_notification_service_dispatches_inbox_changed_event(): void
    {
        Event::fake([InboxChanged::class]);

        $recipient = User::factory()->create();
        $recipient->assignRole(Role::TEACHER);

        $service = app(NotificationService::class);
        $notification = $service->createInboxNotification(
            recipient: $recipient,
            eventCategory: 'USER_MANAGEMENT',
            summary: 'Profil Anda telah disinkronkan.',
            scopeType: 'USER',
            scopeId: $recipient->id
        );

        Event::assertDispatched(InboxChanged::class, function (InboxChanged $event) use ($recipient, $notification) {
            $channels = $event->broadcastOn();
            $this->assertCount(1, $channels);
            $this->assertEquals('private-users.'.$recipient->id, $channels[0]->name);
            $this->assertEquals('inbox.changed.v1', $event->broadcastAs());

            $payload = $event->broadcastWith();
            $this->assertSame(['notification_id' => $notification->id], $payload);

            return true;
        });
    }
}
