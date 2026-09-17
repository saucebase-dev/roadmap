<?php

use Saucebase\Core\Facades\Navigation;
use Saucebase\Core\Navigation\Section;

/*
|--------------------------------------------------------------------------
| Roadmap Module Navigation
|--------------------------------------------------------------------------
|
| Define Roadmap module navigation items here.
| These items will be loaded automatically when the module is enabled.
|
*/

Navigation::add('Roadmap', fn () => route('roadmap.index'), function (Section $section) {
    $section->attributes([
        'group' => 'landing',
        'slug' => 'roadmap',
        'icon' => 'roadmap',
        'order' => 2,
    ]);
});

Navigation::add('Roadmap', fn () => route('roadmap.index'), function (Section $section) {
    $section->attributes([
        'group' => 'secondary',
        'slug' => 'roadmap',
        'icon' => 'roadmap',
        // Just above "Star us on Github", which sits at 0.
        'order' => -1,
    ]);
});
