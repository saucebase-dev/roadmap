<?php

namespace Modules\Roadmap\Models;

use App\Models\User;
use Carbon\Carbon;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Enums\RoadmapType;
use Modules\Roadmap\Events\StatusChanged;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property RoadmapStatus $status
 * @property RoadmapType $type
 * @property int|null $user_id
 * @property string|null $official_response
 * @property Carbon|null $official_response_at
 * @property int|null $merged_into_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int $votes_count
 * @property-read int $comments_count
 */
class RoadmapItem extends Model implements Sitemapable
{
    use HasFactory, Sluggable;

    /**
     * What a listing needs alongside each item.
     *
     * @var array<int, string>
     */
    private const COUNTS = ['votes', 'visibleComments as comments_count'];

    protected static function booted(): void
    {
        static::saving(function (RoadmapItem $item): void {
            if ($item->isDirty('official_response')) {
                $item->official_response_at = filled($item->official_response) ? now() : null;
            }
        });

        static::updated(function (RoadmapItem $item): void {
            if ($item->wasChanged('status')) {
                StatusChanged::dispatch($item);
            }
        });
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
            ],
        ];
    }

    protected $fillable = [
        'title',
        'slug',
        'description',
        'status',
        'type',
        'user_id',
        'official_response',
        'official_response_at',
        'merged_into_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => RoadmapStatus::class,
            'type' => RoadmapType::class,
            'official_response_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<RoadmapComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(RoadmapComment::class)->oldest();
    }

    /**
     * Comments a visitor gets to see: a hidden one stays in the database for the
     * admin, but never reaches the page.
     *
     * @return HasMany<RoadmapComment, $this>
     */
    public function visibleComments(): HasMany
    {
        return $this->comments()->visible();
    }

    /** @return BelongsTo<RoadmapItem, $this> */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(RoadmapItem::class, 'merged_into_id');
    }

    /** @return HasMany<RoadmapVote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(RoadmapVote::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function voters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'roadmap_votes')->withTimestamps();
    }

    public function url(): string
    {
        return route('roadmap.show', $this->slug);
    }

    public function toSitemapTag(): Url
    {
        return Url::create($this->url())->setLastModificationDate($this->updated_at);
    }

    /**
     * Everyone who asked to hear about this item: the submitter and everyone who voted.
     *
     * @return Collection<int, User>
     */
    public function subscribers(): Collection
    {
        return $this->voters()->get()
            ->when($this->user, fn ($voters) => $voters->push($this->user))
            ->unique('id')
            ->values();
    }

    /** Whether visitors may read, vote on and comment on the item. */
    public function isPublic(): bool
    {
        return in_array($this->status, RoadmapStatus::publicStatuses(), true);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query
            ->whereIn('status', RoadmapStatus::publicStatuses())
            ->withCount(self::COUNTS);
    }

    /**
     * Everything one person submitted, whatever its status: their own item is
     * theirs to see while it waits for review or after it was closed.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeSubmittedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)->withCount(self::COUNTS);
    }
}
