<?php

namespace WebRegulate\LaravelAdministration\Classes;

use Illuminate\Notifications\Notification;

class WRLAResetPasswordNotification extends Notification
{
    /**
     * The user's email address.
     *
     * @var string
     */
    public $email;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new notification instance.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($email, $token)
    {
        $this->email = $email;
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject(config('app.name') . ' - Reset Password')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', url(route('wrla.reset-password', ['email' => $this->email, 'token' => $this->token], true)))
            ->line('If you did not request a password reset, no further action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
