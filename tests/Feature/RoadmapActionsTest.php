<?php

namespace Modules\Roadmap\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Roadmap\Actions\SubmitSuggestion;
use Modules\Roadmap\Actions\ToggleVote;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Enums\RoadmapType;
use Modules\Roadmap\Models\RoadmapItem;
use Tests\TestCase;

class RoadmapActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_suggestion_waits_for_review_with_its_submitters_vote(): void
    {
        $user = User::factory()->create();

        $item = app(SubmitSuggestion::class)->handle($user, 'Dark mode', null, RoadmapType::Feature);

        $this->assertSame(RoadmapStatus::UnderReview, $item->status);
        $this->assertTrue($item->user->is($user));
        $this->assertTrue($item->votes()->where('user_id', $user->id)->exists());
    }

    public function test_toggling_a_vote_adds_it_then_removes_it(): void
    {
        $user = User::factory()->create();
        $item = RoadmapItem::factory()->planned()->create();

        $this->assertTrue(app(ToggleVote::class)->handle($item, $user));
        $this->assertFalse(app(ToggleVote::class)->handle($item, $user));
        $this->assertSame(0, $item->votes()->count());
    }
}
