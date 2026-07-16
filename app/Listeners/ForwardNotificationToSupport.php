<?php

namespace App\Listeners;

use App\Mail\SupportNotificationCopy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class ForwardNotificationToSupport implements ShouldQueue
{
    public function handle(NotificationSent $event): void
    {
        // Only forward once per notification: the database channel is the
        // canonical one for every in-app notification type.
        if ($event->channel !== 'database') {
            return;
        }

        $supportEmail = config('services.support.notification_email');
        if (! $supportEmail) {
            return;
        }

        $data = method_exists($event->notification, 'toArray')
            ? $event->notification->toArray($event->notifiable)
            : [];

        // One business event may notify several users (e.g. a comment notifies
        // requestor + assignee). Forward the support copy only once: the payload
        // is recipient-independent, so identical payloads within the window are
        // the same event. Cache::add is atomic, safe across parallel workers.
        $fingerprint = 'support-forward:'.md5(get_class($event->notification).'|'.json_encode($data));
        if (! Cache::add($fingerprint, true, now()->addSeconds(30))) {
            return;
        }

        $recipientName = $event->notifiable->name ?? (string) $event->notifiable->getKey();

        $lines = array_filter([
            $data['message'] ?? class_basename($event->notification),
            '',
            isset($data['ticket_code']) ? "Tiket: {$data['ticket_code']}" : null,
            isset($data['ticket_title']) ? "Judul: {$data['ticket_title']}" : null,
            isset($data['actor_name']) && $data['actor_name'] ? "Oleh: {$data['actor_name']}" : null,
            "Penerima: {$recipientName}",
        ], fn ($line) => $line !== null);

        $subject = sprintf(
            '[%s] %s',
            config('app.name'),
            $data['message'] ?? class_basename($event->notification),
        );

        Mail::to($supportEmail)->send(
            new SupportNotificationCopy($subject, implode("\n", $lines)),
        );
    }
}
