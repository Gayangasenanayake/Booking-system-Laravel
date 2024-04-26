<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class welcomeMessage extends Notification
{
    use Queueable;

    public  $email,$name;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $name, string $email)
    {
        $this->email= $email;
        $this->name= $name;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('Hello '.$this->name.' welcome to BetterBookings.')
                    ->line('Thank you for using our application!')
                    ->subject('Welcome BetterBookings');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
