<?php

namespace Modules\Roadmap\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Roadmap\Database\Seeders\DatabaseSeeder;
use Modules\Roadmap\Filament\Pages\RoadmapSettings;
use Modules\Roadmap\Filament\Resources\Roadmap\RoadmapItemResource;
use Modules\Roadmap\Filament\Resources\RoadmapComments\RoadmapCommentResource;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The roadmap admin area, settings included, is its own permission, so a role can be
 * given the roadmap and nothing else in the panel.
 */
class RoadmapPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function pages(): array
    {
        return [
            'items' => [RoadmapItemResource::class],
            'comments' => [RoadmapCommentResource::class],
            'settings' => [RoadmapSettings::class],
        ];
    }

    public function test_the_seeder_creates_the_permission(): void
    {
        $this->assertTrue(Permission::where('name', 'manage roadmap')->exists());
    }

    #[DataProvider('pages')]
    public function test_a_roadmap_admin_can_open_the_roadmap(string $page): void
    {
        $this->actingAs($this->staff('access admin panel', 'manage roadmap'))
            ->get($this->urlOf($page))
            ->assertOk();
    }

    #[DataProvider('pages')]
    public function test_panel_access_alone_does_not_open_the_roadmap(string $page): void
    {
        $this->actingAs($this->staff('access admin panel'))
            ->get($this->urlOf($page))
            ->assertForbidden();
    }

    private function urlOf(string $page): string
    {
        return $page === RoadmapSettings::class ? $page::getUrl() : $page::getUrl('index');
    }

    private function staff(string ...$permissions): User
    {
        Permission::findOrCreate('access admin panel');

        return User::factory()->create(['email_verified_at' => now()])->givePermissionTo($permissions);
    }
}
