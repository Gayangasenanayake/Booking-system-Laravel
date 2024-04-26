<?php

namespace Modules\BookingProcess\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class BookingDateChangeNotification extends Notification
{
    use Queueable;

    private string $reference, $oldDate, $newDate;


    /**
     * Create a new notification instance.
     */
    public function __construct(string $reference, string $oldDate, string $newDate)
    {
        $this->reference = $reference;
        $this->oldDate = $oldDate;
        $this->newDate = $newDate;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('Your booking reference number '.$this->reference . 'activity date has been changed from ' . $this->oldDate . ' to ' . $this->newDate)
//            ->action('Notification Action', 'https://laravel.com')
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [];
    }
}
