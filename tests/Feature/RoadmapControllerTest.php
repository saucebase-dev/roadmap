<?php

namespace Modules\Roadmap\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Models\RoadmapItem;
use Tests\TestCase;

class RoadmapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_see_the_roadmap(): void
    {
        $item = RoadmapItem::factory()->planned()->create();

        $this->get(route('roadmap.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('authenticated', false)
            ->where('items.0.id', $item->id)
            ->where('items.0.has_voted', false)
        );
    }

    public function test_index_only_returns_public_items(): void
    {
        $user = User::factory()->create();
        $planned = RoadmapItem::factory()->planned()->create(['title' => 'Planned feature']);
        RoadmapItem::factory()->create(['status' => RoadmapStatus::UnderReview, 'title' => 'Item under review']);
        RoadmapItem::factory()->create(['status' => RoadmapStatus::Closed, 'title' => 'Closed item']);

        $response = $this->actingAs($user)->get(route('roadmap.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('items', 1)
            ->where('items.0.id', $planned->id)
        );
    }

    public function test_items_show_whether_the_user_has_voted(): void
    {
        $user = User::factory()->create();
        $voted = RoadmapItem::factory()->planned()->create();
        RoadmapItem::factory()->planned()->create();
        $voted->votes()->create(['user_id' => $user->id]);
        $voted->votes()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->actingAs($user)->get(route('roadmap.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.id', $voted->id)
            ->where('items.0.has_voted', true)
            ->where('items.0.votes_count', 2)
            ->where('items.1.has_voted', false)
            ->where('items.1.votes_count', 0)
        );
    }

    public function test_items_include_expected_fields(): void
    {
        $user = User::factory()->create();
        RoadmapItem::factory()->planned()->create();

        $response = $this->actingAs($user)->get(route('roadmap.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('items.0', fn ($item) => $item
                ->has('id')
                ->has('title')
                ->has('description')
                ->has('status')
                ->has('status_label')
                ->has('type')
                ->has('type_label')
                ->has('slug')
                ->has('url')
                ->has('votes_count')
                ->has('comments_count')
                ->has('has_voted')
                ->has('created_at')
            )
        );
    }

    public function test_sort_defaults_to_trending(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('roadmap.index'));

        $response->assertInertia(fn ($page) => $page->where('sort', 'trending'));
    }

    public function test_invalid_sort_falls_back_to_trending(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('roadmap.index', ['sort' => 'invalid']));

        $response->assertInertia(fn ($page) => $page->where('sort', 'trending'));
    }

    public function test_items_are_sorted_by_votes_by_default(): void
    {
        $user = User::factory()->create();
        $less = RoadmapItem::factory()->planned()->create();
        $popular = RoadmapItem::factory()->planned()->create();

        $popular->votes()->create(['user_id' => User::factory()->create()->id]);
        $popular->votes()->create(['user_id' => User::factory()->create()->id]);
        $less->votes()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->actingAs($user)->get(route('roadmap.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.id', $popular->id)
            ->where('items.1.id', $less->id)
        );
    }

    public function test_items_can_be_sorted_by_newest(): void
    {
        $user = User::factory()->create();
        $old = RoadmapItem::factory()->planned()->create(['created_at' => now()->subDays(5)]);
        $new = RoadmapItem::factory()->planned()->create(['created_at' => now()]);

        $response = $this->actingAs($user)->get(route('roadmap.index', ['sort' => 'new']));

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.id', $new->id)
            ->where('items.1.id', $old->id)
        );
    }

    public function test_items_can_be_sorted_by_oldest(): void
    {
        $user = User::factory()->create();
        $old = RoadmapItem::factory()->planned()->create(['created_at' => now()->subDays(5)]);
        $new = RoadmapItem::factory()->planned()->create(['created_at' => now()]);

        $response = $this->actingAs($user)->get(route('roadmap.index', ['sort' => 'old']));

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.id', $old->id)
            ->where('items.1.id', $new->id)
        );
    }

    public function test_my_feedback_only_lists_items_i_submitted(): void
    {
        $user = User::factory()->create();
        $mine = RoadmapItem::factory()->planned()->create(['user_id' => $user->id]);
        RoadmapItem::factory()->planned()->create();

        $this->actingAs($user)
            ->get(route('roadmap.index', ['mine' => true]))
            ->assertInertia(fn ($page) => $page
                ->where('mine', true)
                ->has('items', 1)
                ->where('items.0.id', $mine->id)
            );
    }

    public function test_my_feedback_includes_my_items_that_are_not_public_yet(): void
    {
        $user = User::factory()->create();
        $underReview = RoadmapItem::factory()->create([
            'user_id' => $user->id,
            'status' => RoadmapStatus::UnderReview,
        ]);

        $this->actingAs($user)
            ->get(route('roadmap.index', ['mine' => true]))
            ->assertInertia(fn ($page) => $page
                ->has('items', 1)
                ->where('items.0.id', $underReview->id)
                ->where('items.0.status', RoadmapStatus::UnderReview->value)
            );
    }

    public function test_guests_cannot_filter_by_their_own_feedback(): void
    {
        RoadmapItem::factory()->planned()->create();

        $this->get(route('roadmap.index', ['mine' => true]))
            ->assertInertia(fn ($page) => $page->where('mine', false)->has('items', 1));
    }

    public function test_only_50_items_are_returned(): void
    {
        $user = User::factory()->create();
        RoadmapItem::factory()->planned()->count(60)->create();

        $response = $this->actingAs($user)->get(route('roadmap.index'));

        $response->assertInertia(fn ($page) => $page->has('items', 50));
    }
}
