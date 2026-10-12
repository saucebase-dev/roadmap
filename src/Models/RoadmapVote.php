<?php

namespace Modules\Roadmap\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadmapVote extends Model
{
    protected $fillable = [
        'roadmap_item_id',
        'user_id',
    ];

    /** @return BelongsTo<RoadmapItem, $this> */
    public function roadmapItem(): BelongsTo
    {
        return $this->belongsTo(RoadmapItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
