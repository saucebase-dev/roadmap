<?php

namespace Modules\Roadmap\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** PostgreSQL and SQLite do not index a foreign key on their own; MySQL does. */
class ForeignKeyIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function foreignKeys(): array
    {
        return [
            'roadmap_items.user_id' => ['roadmap_items', 'user_id'],
            'roadmap_items.merged_into_id' => ['roadmap_items', 'merged_into_id'],
            'roadmap_votes.user_id' => ['roadmap_votes', 'user_id'],
            'roadmap_comments.roadmap_item_id' => ['roadmap_comments', 'roadmap_item_id'],
            'roadmap_comments.user_id' => ['roadmap_comments', 'user_id'],
        ];
    }

    #[DataProvider('foreignKeys')]
    public function test_every_foreign_key_is_indexed(string $table, string $column): void
    {
        $this->assertTrue(Schema::hasIndex($table, [$column]), "{$table}.{$column} has no index of its own.");
    }
}
