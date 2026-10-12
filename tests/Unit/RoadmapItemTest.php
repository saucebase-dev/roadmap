<?php

namespace Modules\Roadmap\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Models\RoadmapItem;
use Tests\TestCase;

class RoadmapItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_scope_only_includes_public_statuses(): void
    {
        foreach (RoadmapStatus::cases() as $status) {
            RoadmapItem::factory()->create(['status' => $status]);
        }

        $statuses = RoadmapItem::public()->pluck('status')->all();

        $this->assertEqualsCanonicalizing(RoadmapStatus::publicStatuses(), $statuses);
    }

    public function test_public_scope_counts_votes(): void
    {
        $item = RoadmapItem::factory()->planned()->create();
        $item->votes()->create(['user_id' => User::factory()->create()->id]);
        $item->votes()->create(['user_id' => User::factory()->create()->id]);

        $this->assertSame(2, RoadmapItem::public()->first()->votes_count);
    }
}
