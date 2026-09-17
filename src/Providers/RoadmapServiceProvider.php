<?php

namespace Modules\Roadmap\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Roadmap\Models\RoadmapItem;
use Saucebase\Core\Providers\ModuleServiceProvider;
use Saucebase\Core\Sitemap\SitemapRegistry;
use Spatie\Sitemap\Sitemap;

class RoadmapServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->registerRateLimits();

        $this->app->make(SitemapRegistry::class)->add(fn (Sitemap $sitemap) => $sitemap
            ->add(route('roadmap.index'))
            ->add(RoadmapItem::public()->get()));
    }

    /**
     * Writing is cheap for a person and cheap for a script, so each write route
     * gets a burst limit and a slower one behind it. Keyed by user, falling back
     * to the address for anyone the session does not know.
     */
    private function registerRateLimits(): void
    {
        RateLimiter::for('roadmap-suggest', fn (Request $request) => [
            Limit::perMinute(3)->by($this->limitKey($request)),
            Limit::perDay(20)->by($this->limitKey($request)),
        ]);

        RateLimiter::for('roadmap-comment', fn (Request $request) => [
            Limit::perMinute(5)->by($this->limitKey($request)),
            Limit::perHour(30)->by($this->limitKey($request)),
        ]);

        RateLimiter::for('roadmap-vote', fn (Request $request) => Limit::perMinute(30)->by($this->limitKey($request)));
    }

    private function limitKey(Request $request): string
    {
        return (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());
    }
}
