<?php

namespace App\Notifications;

use App\Helpers\Firebase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $data;
    protected $withFcm;

    public function __construct($data, $withFcm = true)
    {
        $this->data = [
            'title' => $data['title'],
            'body' => $data['body'],
            'url' => @$data['url'],
            'type' => 'general',
        ];
        $this->withFcm = $withFcm;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', FirebaseChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toDatabase($notifiable)
    {
        return $this->data;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toFireBase($notifiable)
    {
        if ($this->withFcm) {
            Firebase::notification($notifiable, $this->data);
        }
    }
}
