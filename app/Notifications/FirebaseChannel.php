<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class FirebaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        $notification->toFireBase($notifiable);
    }
}
