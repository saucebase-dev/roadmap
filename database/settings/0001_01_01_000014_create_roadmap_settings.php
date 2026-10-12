<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('roadmap.comments_enabled')) {
            $this->migrator->add('roadmap.comments_enabled', true);
        }
    }
};
