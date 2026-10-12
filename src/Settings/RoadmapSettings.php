<?php

namespace Modules\Roadmap\Settings;

use Spatie\LaravelSettings\Settings;

class RoadmapSettings extends Settings
{
    /** Whether visitors can discuss roadmap items. */
    public bool $comments_enabled;

    public static function group(): string
    {
        return 'roadmap';
    }
}
