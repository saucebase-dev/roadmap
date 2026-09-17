<?php

namespace Modules\Roadmap\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InertiaUI\Modal\Modal;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Models\RoadmapItem;
use Modules\Roadmap\Settings\RoadmapSettings;
use Tests\TestCase;

class RoadmapItemPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_read_an_item_and_its_comments(): void
    {
        $item = RoadmapItem::factory()->planned()->create();
        $item->comments()->create([
            'user_id' => User::factory()->create(['name' => 'Ada Lovelace'])->id,
            'body' => 'Please build this',
        ]);

        $this->get($item->url())->assertOk()->assertInertia(fn ($page) => $page
            ->where('item.title', $item->title)
            ->where('item.comments.0.body', 'Please build this')
            ->where('item.comments.0.author', 'Ada L.')
            ->where('authenticated', false)
        );
    }

    public function test_the_board_can_open_an_item_as_a_modal(): void
    {
        $item = RoadmapItem::factory()->planned()->create();

        $this->withHeader(Modal::HEADER_MODAL, 'true')
            ->get($item->url())
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('modal', true));
    }

    public function test_items_that_are_not_public_are_not_found(): void
    {
        $item = RoadmapItem::factory()->create(['status' => RoadmapStatus::UnderReview]);

        $this->get($item->url())->assertNotFound();
    }

    public function test_the_team_response_is_shown_with_the_date_it_was_written(): void
    {
        $item = RoadmapItem::factory()->planned()->create();
        $item->update(['official_response' => 'Shipping this quarter.']);

        $this->get($item->url())->assertInertia(fn ($page) => $page
            ->where('item.official_response', 'Shipping this quarter.')
            ->whereNot('item.official_response_at', null)
        );
    }

    public function test_guests_cannot_comment(): void
    {
        $item = RoadmapItem::factory()->planned()->create();

        $this->post(route('roadmap.comments.store', $item), ['body' => 'Hi'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('roadmap_comments', 0);
    }

    public function test_a_signed_in_user_can_comment(): void
    {
        $user = User::factory()->create();
        $item = RoadmapItem::factory()->planned()->create();

        $this->actingAs($user)
            ->post(route('roadmap.comments.store', $item), ['body' => 'Great idea'])
            ->assertRedirect();

        $this->assertDatabaseHas('roadmap_comments', [
            'roadmap_item_id' => $item->id,
            'user_id' => $user->id,
            'body' => 'Great idea',
        ]);
    }

    public function test_comments_are_rejected_when_empty_or_too_long(): void
    {
        $user = User::factory()->create();
        $item = RoadmapItem::factory()->planned()->create();

        $this->actingAs($user)
            ->post(route('roadmap.comments.store', $item), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->actingAs($user)
            ->post(route('roadmap.comments.store', $item), ['body' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('roadmap_comments', 0);
    }

    public function test_a_hidden_comment_is_kept_but_never_shown(): void
    {
        $item = RoadmapItem::factory()->planned()->create();
        $item->comments()->create([
            'user_id' => User::factory()->create()->id,
            'body' => 'Spam',
            'hidden_at' => now(),
            'hidden_reason' => 'Spam',
        ]);

        $this->get($item->url())->assertInertia(fn ($page) => $page
            ->has('item.comments', 0)
            ->where('item.comments_count', 0)
        );

        $this->assertDatabaseCount('roadmap_comments', 1);
    }

    public function test_comments_can_be_switched_off(): void
    {
        $settings = app(RoadmapSettings::class);
        $settings->comments_enabled = false;
        $settings->save();

        $item = RoadmapItem::factory()->planned()->create();

        $this->get($item->url())
            ->assertInertia(fn ($page) => $page->where('item.comments_enabled', false));

        $this->actingAs(User::factory()->create())
            ->post(route('roadmap.comments.store', $item), ['body' => 'Hi'])
            ->assertNotFound();

        $this->assertDatabaseCount('roadmap_comments', 0);
    }

    public function test_commenting_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $item = RoadmapItem::factory()->planned()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)
                ->post(route('roadmap.comments.store', $item), ['body' => "Comment {$i}"])
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('roadmap.comments.store', $item), ['body' => 'One too many'])
            ->assertStatus(429);
    }

    public function test_cannot_comment_on_an_item_that_is_not_public(): void
    {
        $item = RoadmapItem::factory()->create(['status' => RoadmapStatus::Closed]);

        $this->actingAs(User::factory()->create())
            ->post(route('roadmap.comments.store', $item), ['body' => 'Hi'])
            ->assertNotFound();
    }
}
