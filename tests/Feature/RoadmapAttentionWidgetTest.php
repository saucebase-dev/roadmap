<?php

namespace Modules\Roadmap\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Filament\Widgets\RoadmapAttentionWidget;
use Modules\Roadmap\Models\RoadmapItem;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RoadmapAttentionWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('manage roadmap');
        $this->actingAs(User::factory()->create()->givePermissionTo('manage roadmap'));
    }

    /** Attention band (20–29): roadmap is 21, after billing. */
    public function test_shown_only_with_suggestions_to_review(): void
    {
        $this->assertSame(21, RoadmapAttentionWidget::getSort());
        RoadmapItem::factory()->create(['status' => RoadmapStatus::Planned]);
        $this->assertFalse(RoadmapAttentionWidget::canView());

        RoadmapItem::factory()->count(2)->create(['status' => RoadmapStatus::UnderReview]);
        $this->assertTrue(RoadmapAttentionWidget::canView());
        Livewire::test(RoadmapAttentionWidget::class)->assertSee(['Suggestions to review', '2']);
    }

    public function test_needs_the_roadmap_permission(): void
    {
        RoadmapItem::factory()->create(['status' => RoadmapStatus::UnderReview]);
        $this->actingAs(User::factory()->create());

        $this->assertFalse(RoadmapAttentionWidget::canView());
    }
}
