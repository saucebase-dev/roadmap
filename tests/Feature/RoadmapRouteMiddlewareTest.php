<?php

namespace Modules\Roadmap\Tests\Feature;

use Illuminate\Routing\Router;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Writing to the board asks for a verified address, like the app's other signed-in
 * routes. `verified` only acts once the User model implements MustVerifyEmail, so this
 * reads the route's middleware rather than a response.
 */
class RoadmapRouteMiddlewareTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function writes(): array
    {
        return [
            'suggest' => ['roadmap.store'],
            'vote' => ['roadmap.vote'],
            'comment' => ['roadmap.comments.store'],
        ];
    }

    #[DataProvider('writes')]
    public function test_writing_to_the_board_needs_a_verified_address(string $name): void
    {
        $router = app(Router::class);
        $route = $router->getRoutes()->getByName($name);

        $this->assertNotNull($route);
        $this->assertContains('verified', $route->gatherMiddleware());
    }
}
