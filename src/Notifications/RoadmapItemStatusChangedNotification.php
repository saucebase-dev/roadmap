<?php

namespace Modules\Roadmap\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Roadmap\Models\RoadmapItem;
use Saucebase\Core\Support\Markdown;

class RoadmapItemStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RoadmapItem $item,
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
            ->subject(__('Your roadmap item status has been updated'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('The status of your roadmap item **":title"** has been updated.', ['title' => Markdown::escape($this->item->title)]))
            ->line(__('New status: **:status**', ['status' => $this->item->status->getLabel()]))
            ->action(__('View Roadmap'), route('roadmap.index'))
            ->line(__('Thank you for your feedback!'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'roadmap_item_id' => $this->item->id,
            'status' => $this->item->status->value,
        ];
    }
}
