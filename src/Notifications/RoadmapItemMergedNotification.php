<?php

namespace Modules\Roadmap\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Roadmap\Models\RoadmapItem;
use Saucebase\Core\Support\Markdown;

/** To the duplicate's voters and author: where their vote and the conversation went. */
class RoadmapItemMergedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RoadmapItem $duplicate,
        public RoadmapItem $target,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('A roadmap item you follow was merged'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('**":duplicate"** was merged into **":target"**, which asks for the same thing. Your vote and the comments now count there.', [
                'duplicate' => Markdown::escape($this->duplicate->title),
                'target' => Markdown::escape($this->target->title),
            ]))
            // A fixed label: Laravel repeats it, unescaped, in the mail's fallback line.
            ->action(__('View the item'), $this->target->url())
            ->line(__('Thank you for your feedback!'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'roadmap_item_id' => $this->duplicate->id,
            'merged_into_id' => $this->target->id,
        ];
    }
}
