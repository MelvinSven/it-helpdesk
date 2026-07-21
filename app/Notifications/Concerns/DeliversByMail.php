<?php

namespace App\Notifications\Concerns;

trait DeliversByMail
{
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }
}
