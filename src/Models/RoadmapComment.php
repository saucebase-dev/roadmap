<?php

namespace Modules\Roadmap\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $body
 * @property Carbon|null $hidden_at
 * @property string|null $hidden_reason
 * @property Carbon $created_at
 */
class RoadmapComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'roadmap_item_id',
        'user_id',
        'body',
        'hidden_at',
        'hidden_reason',
    ];

    protected function casts(): array
    {
        return [
            'hidden_at' => 'datetime',
        ];
    }

    /** @param  Builder<$this>  $query */
    public function scopeVisible(Builder $query): void
    {
        $query->whereNull('hidden_at');
    }

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
