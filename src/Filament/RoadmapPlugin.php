<?php

namespace Modules\Roadmap\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Saucebase\Core\Filament\ModulePlugin;

class RoadmapPlugin implements Plugin
{
    use ModulePlugin;

    public function getModuleName(): string
    {
        return 'Roadmap';
    }

    public function getId(): string
    {
        return 'roadmap';
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
