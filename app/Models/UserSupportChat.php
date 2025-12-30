<?php

namespace App\Models;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSupportChat extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $with = [
        'supportTicket',
    ];

    protected $appends = ['senderImage'];

    public function scopeConversations($query, $id)
    {
        return $query->where('user_support_ticket_id', $id);
    }

    public function supportTicket()
    {
        return $this->belongsTo(UserSupportTicket::class, 'user_support_ticket_id');
    }

    public function getSenderImageAttribute()
    {
        if ($this->sender_type == 'ADMIN') {
            $admin = Admin::find($this->sender);
            if ($admin) {
                return get_image($admin->image, 'admin-profile');
            } else {
                return files_asset_path('default');
            }
        } elseif ($this->sender_type == 'USER') {
            return $this->supportTicket->user->userImage;
        } elseif ($this->sender_type == 'MERCHANT') {
            return $this->supportTicket->merchant->userImage;
        } elseif ($this->sender_type == 'AGENT') {
            return $this->supportTicket->agent->agentImage;
        }

        return files_asset_path('default');
    }
}
