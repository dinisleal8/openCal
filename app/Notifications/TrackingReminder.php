<?php

namespace App\Notifications;

use App\Enums\ReminderType;
use App\Models\User;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TrackingReminder extends Notification
{
    public function __construct(public ReminderType $type) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toWebPush(User $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title(__($this->type->titleKey()))
            ->body(__($this->type->bodyKey()))
            ->icon('/icons/notification-icon.png')
            ->badge('/icons/notification-badge.png')
            ->tag("opencal-{$this->type->value}")
            ->data(['url' => $this->type->url()]);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'title' => __($this->type->titleKey()),
            'body' => __($this->type->bodyKey()),
            'url' => $this->type->url(),
        ];
    }
}
