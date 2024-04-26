<?php

namespace Modules\BookingProcess\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class BookingConfirmationNotification extends Notification
{
    use Queueable;

    private string $tenant;
    private string $reference;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $tenant, string $reference)
    {
        $this->tenant = $tenant;
        $this->reference = $reference;
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
            ->line('Your booking is confirmed Booking reference: ' . $this->reference)
            ->action('Update your booking', ENV('BOOKING_PROCESS_FRONTEND_URL').'auth/login?tenant=' . $this->tenant)
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
