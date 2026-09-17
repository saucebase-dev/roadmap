<?php

namespace Modules\Roadmap\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Events\StatusChanged;
use Modules\Roadmap\Models\RoadmapItem;
use Modules\Roadmap\Notifications\RoadmapItemStatusChangedNotification;
use Tests\TestCase;

class RoadmapNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_is_dispatched_when_status_changes(): void
    {
        Event::fake([StatusChanged::class]);

        $item = RoadmapItem::factory()->create(['status' => RoadmapStatus::UnderReview]);

        $item->update(['status' => RoadmapStatus::Planned]);

        Event::assertDispatched(StatusChanged::class, function ($event) use ($item) {
            return $event->item->is($item);
        });
    }

    public function test_event_is_not_dispatched_when_status_does_not_change(): void
    {
        Event::fake([StatusChanged::class]);

        $item = RoadmapItem::factory()->create(['status' => RoadmapStatus::Planned]);

        $item->update(['title' => 'Updated title only']);

        Event::assertNotDispatched(StatusChanged::class);
    }

    public function test_user_is_notified_when_status_changes(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $item = RoadmapItem::factory()->create([
            'status' => RoadmapStatus::UnderReview,
            'user_id' => $user->id,
        ]);

        $item->update(['status' => RoadmapStatus::Planned]);

        Notification::assertSentTo($user, RoadmapItemStatusChangedNotification::class);
    }

    public function test_voters_are_notified_when_status_changes(): void
    {
        Notification::fake();

        $submitter = User::factory()->create();
        $voter = User::factory()->create();
        $bystander = User::factory()->create();

        $item = RoadmapItem::factory()->create([
            'status' => RoadmapStatus::UnderReview,
            'user_id' => $submitter->id,
        ]);
        $item->votes()->create(['user_id' => $voter->id]);

        $item->update(['status' => RoadmapStatus::Planned]);

        Notification::assertSentTo([$submitter, $voter], RoadmapItemStatusChangedNotification::class);
        Notification::assertNotSentTo($bystander, RoadmapItemStatusChangedNotification::class);
    }

    public function test_a_title_cannot_smuggle_a_link_into_the_email(): void
    {
        $user = User::factory()->create();
        $item = RoadmapItem::factory()->create([
            'status' => RoadmapStatus::UnderReview,
            'user_id' => $user->id,
            'title' => 'Evil [click me](https://evil.test)',
        ]);

        $mail = (new RoadmapItemStatusChangedNotification($item))->toMail($user);

        $this->assertStringNotContainsString('href="https://evil.test"', (string) $mail->render());
    }

    public function test_notification_is_not_sent_when_status_does_not_change(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $item = RoadmapItem::factory()->create([
            'status' => RoadmapStatus::Planned,
            'user_id' => $user->id,
        ]);

        $item->update(['title' => 'Updated title only']);

        Notification::assertNothingSent();
    }

    public function test_notification_is_not_sent_for_items_without_a_user(): void
    {
        Notification::fake();

        $item = RoadmapItem::factory()->create([
            'status' => RoadmapStatus::UnderReview,
            'user_id' => null,
        ]);

        $item->update(['status' => RoadmapStatus::Planned]);

        Notification::assertNothingSent();
    }

    public function test_notification_email_contains_item_title(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);
        $item = RoadmapItem::factory()->create([
            'title' => 'Dark mode support',
            'status' => RoadmapStatus::Planned,
            'user_id' => $user->id,
        ]);

        $notification = new RoadmapItemStatusChangedNotification($item);
        $mail = $notification->toMail($user);

        $this->assertStringContainsString('Dark mode support', implode(' ', $mail->introLines));
    }

    public function test_notification_email_contains_new_status(): void
    {
        $user = User::factory()->create();
        $item = RoadmapItem::factory()->inProgress()->create(['user_id' => $user->id]);

        $notification = new RoadmapItemStatusChangedNotification($item);
        $mail = $notification->toMail($user);

        $this->assertStringContainsString('In Progress', implode(' ', $mail->introLines));
    }

    public function test_notification_email_has_roadmap_action_link(): void
    {
        $user = User::factory()->create();
        $item = RoadmapItem::factory()->planned()->create(['user_id' => $user->id]);

        $notification = new RoadmapItemStatusChangedNotification($item);
        $mail = $notification->toMail($user);

        $this->assertEquals(route('roadmap.index'), $mail->actionUrl);
    }

    public function test_notification_is_sent_for_each_status_transition(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $item = RoadmapItem::factory()->create([
            'status' => RoadmapStatus::UnderReview,
            'user_id' => $user->id,
        ]);

        $item->update(['status' => RoadmapStatus::Planned]);
        $item->update(['status' => RoadmapStatus::InProgress]);
        $item->update(['status' => RoadmapStatus::Shipped]);

        Notification::assertSentToTimes($user, RoadmapItemStatusChangedNotification::class, 3);
    }
}
