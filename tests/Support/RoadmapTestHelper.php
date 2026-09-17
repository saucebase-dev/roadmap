<?php

namespace Modules\Roadmap\Tests\Support;

use Modules\Roadmap\Models\RoadmapComment;
use Modules\Roadmap\Models\RoadmapItem;
use Modules\Roadmap\Models\RoadmapVote;

class RoadmapTestHelper
{
    public static function clean(): void
    {
        RoadmapComment::query()->delete();
        RoadmapVote::query()->delete();
        RoadmapItem::query()->delete();
    }
}
