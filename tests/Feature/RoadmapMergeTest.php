<?php

namespace Modules\Roadmap\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Models\RoadmapItem;
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

        $duplicate->mergeInto($target);

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
            $item->mergeInto($item);
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

        $duplicate->mergeInto($target);

        $this->assertSame(1, $target->votes()->count());
    }
}
