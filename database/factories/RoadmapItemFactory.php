<?php

namespace Modules\Roadmap\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Enums\RoadmapType;
use Modules\Roadmap\Models\RoadmapItem;

/**
 * @extends Factory<RoadmapItem>
 */
class RoadmapItemFactory extends Factory
{
    protected $model = RoadmapItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4, false),
            'description' => $this->faker->paragraph(),
            'status' => RoadmapStatus::UnderReview,
            'type' => $this->faker->randomElement(RoadmapType::cases()),
            'user_id' => User::factory(),
        ];
    }

    public function planned(): static
    {
        return $this->state(['status' => RoadmapStatus::Planned]);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => RoadmapStatus::InProgress]);
    }

    public function shipped(): static
    {
        return $this->state(['status' => RoadmapStatus::Shipped]);
    }

    public function feature(): static
    {
        return $this->state(['type' => RoadmapType::Feature]);
    }

    public function bug(): static
    {
        return $this->state(['type' => RoadmapType::Bug]);
    }

    public function improvement(): static
    {
        return $this->state(['type' => RoadmapType::Improvement]);
    }
}
