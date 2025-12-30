<?php

namespace App\Notifications\User\ClubKonnect;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CableTvPurchaseMail extends Notification
{
    use Queueable;

    protected $user;
    protected $data;

    public function __construct($user, $data)
    {
        $this->user = $user;
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('Cable TV Subscription Confirmation')
            ->greeting('Hello ' . $this->user->username . ',')
            ->line('Your cable TV subscription request has been processed.')
            ->line('Here are the details:')
            ->line('📺 Provider: ' . $this->data->provider)
            ->line('📦 Package: ' . $this->data->package)
            ->line('💳 Amount: ' . $this->data->amount)
            ->line('📱 Smartcard: ' . $this->data->smartcard)
            ->line('📌 Status: ' . strtoupper($this->data->status))
            ->line('💰 Wallet Balance: ' . $this->data->current_balance)
            ->line('🕒 Date: ' . now()->format('Y-m-d h:i:s A'))
            ->line('Thank you for using our platform.');
    }
}
