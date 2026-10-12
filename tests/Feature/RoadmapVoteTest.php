<?php

namespace Modules\Roadmap\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Models\RoadmapItem;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoadmapVoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_vote_and_is_redirected(): void
    {
        $item = RoadmapItem::factory()->planned()->create();

        $this->post(route('roadmap.vote', $item))->assertRedirect(route('login'));

        $this->assertDatabaseCount('roadmap_votes', 0);
    }

    public function test_voting_adds_an_upvote_and_voting_again_removes_it(): void
    {
        $user = User::factory()->create();
        $item = RoadmapItem::factory()->planned()->create();

        $this->actingAs($user)->post(route('roadmap.vote', $item))->assertRedirect();
        $this->assertDatabaseHas('roadmap_votes', ['roadmap_item_id' => $item->id, 'user_id' => $user->id]);

        $this->actingAs($user)->post(route('roadmap.vote', $item));
        $this->assertDatabaseCount('roadmap_votes', 0);
    }

    public function test_removing_a_vote_keeps_other_users_votes(): void
    {
        $item = RoadmapItem::factory()->planned()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA)->post(route('roadmap.vote', $item));
        $this->actingAs($userB)->post(route('roadmap.vote', $item));
        $this->actingAs($userA)->post(route('roadmap.vote', $item));

        $this->assertDatabaseCount('roadmap_votes', 1);
        $this->assertDatabaseHas('roadmap_votes', ['user_id' => $userB->id]);
    }

    #[DataProvider('hiddenStatuses')]
    public function test_cannot_vote_on_items_that_are_not_public(RoadmapStatus $status): void
    {
        $item = RoadmapItem::factory()->create(['status' => $status]);

        $this->actingAs(User::factory()->create())
            ->post(route('roadmap.vote', $item))
            ->assertNotFound();

        $this->assertDatabaseCount('roadmap_votes', 0);
    }

    /** @return array<string, array{RoadmapStatus}> */
    public static function hiddenStatuses(): array
    {
        return [
            'under review' => [RoadmapStatus::UnderReview],
            'closed' => [RoadmapStatus::Closed],
        ];
    }
}
