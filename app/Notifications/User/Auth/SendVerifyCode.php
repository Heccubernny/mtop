<?php

namespace App\Notifications\User\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SendVerifyCode extends Notification
{
    use Queueable;

    public $email;

    public $code;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($email, $code)
    {
        $this->email = $email;
        $this->code = $code;

        Log::info('SendVerifyCode notification instantiated.', [
            'email' => $email,
            'code' => $code,
        ]);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        Log::info('SendVerifyCode: via() called.');

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
        Log::info('SendVerifyCode: toMail() called.', [
            'to' => $this->email,
            'code' => $this->code,
        ]);
        $code = $this->code;
        $username = explode('@', $this->email);

        return (new MailMessage)
            ->greeting(__('Hello').' '.@$username[0].' !')
            ->subject(__('Verification Code ( Register )'))
            ->line(__('You are trying to verify code for register.'))
            ->line(__('Here is your OTP').': '.$code)
            ->line(__('Thank you for using our application!'));
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
