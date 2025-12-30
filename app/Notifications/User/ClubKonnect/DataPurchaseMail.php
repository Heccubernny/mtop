<?php

namespace App\Notifications\User\ClubKonnect;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class DataPurchaseMail extends Notification
{
    use Queueable;

    public $user;
    public $data;

    /**
     * Create a new notification instance.
     *
     * $data MUST contain:
     * trx_id, network, mobile, amount, plan, volume, status, current_balance
     */
    public function __construct($user, $data)
    {
        $this->user = $user;
        $this->data = $data;
    }

    /**
     * Notification delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Email message.
     */
    public function toMail($notifiable)
    {
        $user = $this->user;
        $data = $this->data;

        $trx_id   = $data->trx_id;
        $dateTime = Carbon::now()->format('Y-m-d h:i:s A');

        return (new MailMessage())
            ->greeting(__('Hello') . ' ' . $user->fullname . '!')
            ->subject(__('Data Purchase for ') . $data->network . ' (' . $data->mobile . ')')
            ->line(__('Data Purchase Details:'))
            ->line(__('web_trx_id') . ': ' . $trx_id)
            ->line(__('Network') . ': ' . $data->network)
            ->line(__('Mobile Number') . ': ' . $data->mobile)
            ->line(__('Plan') . ': ' . $data->plan . ' (' . $data->volume . ')')
            ->line(__('Amount Paid') . ': ' . get_amount($data->amount))
            ->line(__('Status') . ': ' . $data->status)
            ->line(__('Current Wallet Balance') . ': ' . get_amount($data->current_balance))
            ->line(__('Date And Time') . ': ' . $dateTime)
            ->line(__('Thank you for using our application!'));
    }

    /**
     * Array representation (not used for now)
     */
    public function toArray($notifiable)
    {
        return [];
    }
}
