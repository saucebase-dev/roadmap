<?php

namespace Modules\Roadmap\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Roadmap\Enums\RoadmapStatus;
use Modules\Roadmap\Enums\RoadmapType;
use Modules\Roadmap\Models\RoadmapItem;

class DemoRoadmapDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title' => 'Stripe usage-based billing',
                'description' => 'Charge per seat, per API call, or per any metric you report, on top of the flat subscription plans the billing module already handles.',
                'status' => RoadmapStatus::InProgress,
                'type' => RoadmapType::Feature,
                'votes' => 24,
                'comments' => [
                    'We bill per API call and currently patch Cashier by hand. This would save us a lot.',
                    'Would this cover metered trials as well?',
                ],
            ],
            [
                'title' => 'Team workspaces with roles and invites',
                'description' => 'Let one account hold several workspaces, invite teammates by email, and scope every module to the workspace the user is currently in.',
                'status' => RoadmapStatus::InProgress,
                'type' => RoadmapType::Feature,
                'votes' => 31,
                'comments' => [
                    'This is the one feature keeping us on a custom build.',
                ],
            ],
            [
                'title' => 'React parity for every module',
                'description' => 'Ship the React implementation of each module alongside the Vue one, so picking a stack never costs you features.',
                'status' => RoadmapStatus::InProgress,
                'type' => RoadmapType::Improvement,
                'votes' => 18,
                'comments' => [],
            ],
            [
                'title' => 'Admin activity log',
                'description' => 'Record who changed what in the admin panel, with a filterable history per record.',
                'status' => RoadmapStatus::Planned,
                'type' => RoadmapType::Feature,
                'votes' => 15,
                'comments' => [
                    'Needed for SOC 2. Export to CSV would help too.',
                ],
            ],
            [
                'title' => 'Customer-facing API keys and webhooks',
                'description' => 'Give your users API keys with scopes and rate limits, plus outgoing webhooks with retries and a delivery log.',
                'status' => RoadmapStatus::Planned,
                'type' => RoadmapType::Feature,
                'votes' => 12,
                'comments' => [],
            ],
            [
                'title' => 'Module installer for private modules',
                'description' => 'Point the installer at your own Composer repository so in-house modules install exactly like the first-party ones.',
                'status' => RoadmapStatus::Planned,
                'type' => RoadmapType::Feature,
                'votes' => 7,
                'comments' => [],
            ],
            [
                'title' => 'Email templates you can edit in the admin',
                'description' => 'Change the wording, logo, and colours of transactional emails without touching Blade files.',
                'status' => RoadmapStatus::Backlog,
                'type' => RoadmapType::Feature,
                'votes' => 9,
                'comments' => [],
            ],
            [
                'title' => 'Self-serve plan changes with proration',
                'description' => 'Let customers upgrade, downgrade, and switch between monthly and yearly from the billing portal, with the proration shown before they confirm.',
                'status' => RoadmapStatus::Backlog,
                'type' => RoadmapType::Feature,
                'votes' => 11,
                'comments' => [
                    'Showing the prorated amount before confirming is the important half.',
                ],
            ],
            [
                'title' => 'Dark mode flickers on first paint',
                'description' => 'On a hard refresh the light theme shows for a moment before the saved preference is applied.',
                'status' => RoadmapStatus::Backlog,
                'type' => RoadmapType::Bug,
                'votes' => 6,
                'comments' => [],
            ],
            [
                'title' => 'One-command deploy to Laravel Cloud',
                'description' => 'A starter deployment config and a single command that provisions the database, queue, and storage.',
                'status' => RoadmapStatus::Shipped,
                'type' => RoadmapType::Feature,
                'votes' => 20,
                'comments' => [
                    'Took about ten minutes end to end. Thanks!',
                ],
            ],
            [
                'title' => 'Blog module with SEO and a sitemap',
                'description' => 'Posts, categories, cover images, structured data, and an automatic sitemap entry for every published post.',
                'status' => RoadmapStatus::Shipped,
                'type' => RoadmapType::Feature,
                'votes' => 14,
                'comments' => [],
            ],
            [
                'title' => 'Announcement banners with scheduling',
                'description' => 'Site-wide banners with start and end dates, audience targeting, and dismissal that sticks.',
                'status' => RoadmapStatus::Shipped,
                'type' => RoadmapType::Feature,
                'votes' => 8,
                'comments' => [],
            ],
            [
                'title' => 'Import our existing users from Auth0',
                'description' => 'Not something we plan to build into the starter kit, and it is a one-off script for most teams.',
                'status' => RoadmapStatus::Closed,
                'type' => RoadmapType::Feature,
                'votes' => 2,
                'comments' => [],
            ],
            [
                'title' => 'Bundle a CRM',
                'description' => 'Out of scope: Saucebase is a starter kit, not a suite. Use the API and webhooks to talk to the CRM you already pay for.',
                'status' => RoadmapStatus::UnderReview,
                'type' => RoadmapType::Feature,
                'votes' => 1,
                'comments' => [],
            ],
        ];

        // Votes and comments need people behind them, and the counts above are
        // the point of the demo data, so top the pool up to the busiest item.
        $needed = max(array_column($items, 'votes'));
        $voters = User::query()->orderBy('id')->take($needed)->get();

        if ($voters->count() < $needed) {
            $voters = $voters->concat(User::factory()->count($needed - $voters->count())->create());
        }

        foreach ($items as $index => $data) {
            // Each item starts further along the same ordered pool, so the first
            // accounts (the admin included) are not on every single item, and a
            // reseed picks the same people again instead of piling votes on.
            $offset = ($index * 3) % max($voters->count(), 1);
            $pool = $voters->slice($offset)->concat($voters->take($offset))->values();

            $item = RoadmapItem::updateOrCreate(
                ['title' => $data['title']],
                collect($data)->except('votes', 'comments')->all(),
            );

            $pool->take($data['votes'])
                ->each(fn (User $user) => $item->votes()->firstOrCreate(['user_id' => $user->id]));

            foreach ($data['comments'] as $commentIndex => $body) {
                $author = $pool->get($commentIndex);

                if (! $author) {
                    continue;
                }

                // Keyed on the text alone: the voter pool is shuffled, so keying
                // on the author too would add a copy on every reseed.
                $item->comments()->firstOrCreate(
                    ['body' => $body],
                    ['user_id' => $author->id],
                );
            }
        }
    }
}
