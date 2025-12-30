<?php

namespace App\Models;

use App\Constants\SupportTicketConst;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSupportTicket extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $with = [
        'user',
        'attachments',
    ];

    protected $appends = ['type', 'stringStatus', 'imagePath'];

    public function scopeAuthTickets($query)
    {
        $query->where('user_id', auth()->user()->id);
    }

    public function scopeAuthTicketsAgent($query)
    {
        $query->where('agent_id', auth()->user()->id);
    }

    public function scopeAuthTicketsMerchant($query)
    {
        $query->where('merchant_id', auth()->user()->id);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function creator()
    {
        if ($this->user_id != null) {
            return $this->user();
        } elseif ($this->agent_id != null) {
            return $this->agent();
        } elseif ($this->merchant_id != null) {
            return $this->merchant();
        }
    }

    public function attachments()
    {
        return $this->hasMany(UserSupportTicketAttachment::class);
    }

    public function getTypeAttribute()
    {
        if ($this->user_id != null) {
            return 'USER';
        } elseif ($this->agent_id != null) {
            return 'AGENT';
        } elseif ($this->merchant_id != null) {
            return 'MERCHANT';
        }

    }

    public function getImagePathAttribute()
    {
        if ($this->user_id != null) {
            return 'user-profile';
        } elseif ($this->agent_id != null) {
            return 'agent-profile';
        } elseif ($this->merchant_id != null) {
            return 'merchant-profile';
        }

    }

    public function conversations()
    {
        return $this->hasMany(UserSupportChat::class, 'user_support_ticket_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', SupportTicketConst::PENDING)->orWhere('status', SupportTicketConst::DEFAULT);
    }

    public function scopeActive($query)
    {
        return $query->where('status', SupportTicketConst::ACTIVE);
    }

    public function scopeSolved($query)
    {
        return $query->where('status', SupportTicketConst::SOLVED);
    }

    public function scopeNotSolved($query, $token)
    {
        $query->where('token', $token)->where('status', '!=', SupportTicketConst::SOLVED);
    }

    public function getStringStatusAttribute()
    {
        $status = $this->status;
        $data = [
            'class' => '',
            'value' => '',
        ];
        if ($status == SupportTicketConst::ACTIVE) {
            $data = [
                'class' => 'badge badge--info',
                'value' => 'active',
            ];
        } elseif ($status == SupportTicketConst::DEFAULT) {
            $data = [
                'class' => 'badge badge--warning',
                'value' => 'Pending',
            ];
        } elseif ($status == SupportTicketConst::PENDING) {
            $data = [
                'class' => 'badge badge--warning',
                'value' => 'Pending',
            ];
        } elseif ($status == SupportTicketConst::SOLVED) {
            $data = [
                'class' => 'badge badge--success',
                'value' => 'Solved',
            ];
        }

        return (object) $data;
    }
}
