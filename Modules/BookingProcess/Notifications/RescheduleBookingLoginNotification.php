<?php

namespace Modules\BookingProcess\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RescheduleBookingLoginNotification extends Notification
{
    use Queueable;

    private string $verificationCode;
    private string $email;
    private string $tenantId;
    private string $reference;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $email, string $verificationCode, string $tenantId, string $reference)
    {
        $this->email = $email;
        $this->verificationCode = $verificationCode;
        $this->tenantId = $tenantId;
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
            ->line('The introduction to the notification.')
            ->action('Notification Action', ENV('BOOKING_PROCESS_FRONTEND_URL').'auth/verify-booking-process?tenant=' . $this->tenantId .'&verification=' . $this->verificationCode. '&reference=' . $this->reference)
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
