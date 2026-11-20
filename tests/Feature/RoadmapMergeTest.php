<?php

namespace Modules\Roadmap\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Roadmap\Actions\MergeItem;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Models\RoadmapItem;
use Modules\Roadmap\Notifications\RoadmapItemMergedNotification;
use Modules\Roadmap\Notifications\RoadmapItemStatusChangedNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RoadmapMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_merging_moves_votes_and_comments_and_closes_the_duplicate(): void
    {
        $duplicate = RoadmapItem::factory()->planned()->create();
        $target = RoadmapItem::factory()->planned()->create();

        $duplicate->votes()->create(['user_id' => User::factory()->create()->id]);
        $duplicate->comments()->create([
            'user_id' => User::factory()->create()->id,
            'body' => 'Same idea',
        ]);

        app(MergeItem::class)->handle($duplicate, $target);

        $this->assertSame(1, $target->votes()->count());
        $this->assertSame(1, $target->comments()->count());
        $this->assertSame(0, $duplicate->votes()->count());
        $this->assertSame($target->id, $duplicate->fresh()->merged_into_id);
        $this->assertSame(RoadmapStatus::Closed, $duplicate->fresh()->status);
    }

    public function test_an_item_cannot_be_merged_into_itself(): void
    {
        $item = RoadmapItem::factory()->planned()->create();
        $item->votes()->create(['user_id' => User::factory()->create()->id]);

        $this->expectException(\InvalidArgumentException::class);

        try {
            app(MergeItem::class)->handle($item, $item);
        } finally {
            $this->assertSame(1, $item->votes()->count());
            $this->assertNull($item->fresh()->merged_into_id);
        }
    }

    public function test_a_user_who_voted_on_both_items_only_counts_once(): void
    {
        $duplicate = RoadmapItem::factory()->planned()->create();
        $target = RoadmapItem::factory()->planned()->create();
        $user = User::factory()->create();

        $duplicate->votes()->create(['user_id' => $user->id]);
        $target->votes()->create(['user_id' => $user->id]);

        app(MergeItem::class)->handle($duplicate, $target);

        $this->assertSame(1, $target->votes()->count());
    }

    /** Voters follow their votes there, so the target must be one they can see. */
    public function test_an_item_is_only_merged_into_a_public_one(): void
    {
        $duplicate = RoadmapItem::factory()->planned()->create();
        $hidden = RoadmapItem::factory()->create(['status' => RoadmapStatus::UnderReview]);

        $this->expectException(\InvalidArgumentException::class);

        try {
            app(MergeItem::class)->handle($duplicate, $hidden);
        } finally {
            $this->assertNull($duplicate->fresh()->merged_into_id);
        }
    }

    /** A link to the duplicate, shared or bookmarked, still reaches the conversation. */
    public function test_the_duplicates_address_leads_to_the_item_it_was_merged_into(): void
    {
        $duplicate = RoadmapItem::factory()->planned()->create();
        $target = RoadmapItem::factory()->planned()->create();

        app(MergeItem::class)->handle($duplicate, $target);

        $this->get($duplicate->url())->assertStatus(301)->assertRedirect($target->url());
    }

    /** Their vote moved: voters and the author hear where, once, and nothing about "Closed". */
    public function test_voters_and_the_author_are_told_where_it_went(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $duplicate = RoadmapItem::factory()->planned()->create(['user_id' => $author->id]);
        $target = RoadmapItem::factory()->planned()->create();
        $duplicate->votes()->create(['user_id' => $voter->id]);

        app(MergeItem::class)->handle($duplicate, $target);

        foreach ([$author, $voter] as $user) {
            Notification::assertSentTo($user, RoadmapItemMergedNotification::class, fn ($n) => $n->target->is($target));
        }
        Notification::assertNotSentTo([$author, $voter], RoadmapItemStatusChangedNotification::class);
    }

    public function test_titles_cannot_smuggle_a_link_into_the_merged_email(): void
    {
        $user = User::factory()->create();
        $duplicate = RoadmapItem::factory()->planned()->create(['title' => 'Evil [one](https://evil.test)']);
        $target = RoadmapItem::factory()->planned()->create(['title' => 'Evil [two](https://evil.test)']);

        $mail = (new RoadmapItemMergedNotification($duplicate, $target))->toMail($user);

        $this->assertStringNotContainsString('href="https://evil.test"', (string) $mail->render());
    }

    public function test_the_merged_email_names_both_items_and_links_to_the_target(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);
        $duplicate = RoadmapItem::factory()->planned()->create(['title' => 'Dark mode']);
        $target = RoadmapItem::factory()->planned()->create(['title' => 'Themes']);

        $mail = (new RoadmapItemMergedNotification($duplicate, $target))->toMail($user);

        $this->assertStringContainsString('Dark mode', implode(' ', $mail->introLines));
        $this->assertStringContainsString('Themes', implode(' ', $mail->introLines));
        $this->assertSame($target->url(), $mail->actionUrl);
    }
}
