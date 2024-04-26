<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable;

    public $token, $email;

    /**
     * Create a new notification instance.
     *
     * @param string $token
     */
    public function __construct(string $token, string $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $resetUrl = url('https://' . env('BACKEND_URL') . '/api/email_verify?code='.$this->token.'&email='.$this->email);

        return (new MailMessage)
            ->line('You have requested to reset your password. Click the button below to reset your password.')
            ->line('Or enter this authentication code: '.$this->token)
            ->action('Reset Password', $resetUrl)
            ->line('If you did not request a password reset, no further action is required.')
            ->subject('Reset Password Notification');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [];
    }
}
